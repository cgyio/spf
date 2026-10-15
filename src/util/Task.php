<?php
/**
 * 框架 特殊工具类
 * 框架长耗时任务类，用于处理一些需要分多步骤执行的操作
 * 例如：执行复杂的多数据表配合的 curd 操作，需要按顺序执行，失败时统一回滚
 * 通常作为接口响应处理类，配合前端的进度条，执行可视化的长耗时任务操作
 * 
 * 流程：
 *  0   创建 Task 实例：
 *      0.1     前端访问 /api/some_task/create 接口
 *      0.2     接口内将创建 Task 实例，添加所有步骤
 *              如果一切正常，修改 Task 实例状态：
 *                  $task->status = running
 *                  $task->stepTo = 0
 *                  修改第一步 TaskStep 的步骤状态：
 *                      $task->step[0]->status = running
 *              persist 持久化 Task 完整状态到缓存文件
 *              !! 只有状态数据会被缓存，所有 closure 闭包方法不会被缓存
 *              返回状态数据到前端（包含 taskId 任务唯一编码），完成 Task 创建步骤
 * 
 *  1   分步执行 Task：
 *      1.1     前端以 轮询 或 步进 方式，多次调用 /api/some_task/next 接口，需要 post 这些数据到接口：
 *              [
 *                  !! 必须的
 *                  "taskId" => "...",
 *                  !! 可选的
 *                  "skipStep" => true | n,     是否跳过 当前步骤，或 传入序号的步骤
 *                  "skipRollback" => true | n, 是否跳过 当前步骤，或 传入序号对应步骤的 rollback 方法
 *                  "skipTaskRollback" => true, 是否跳过全局回滚方法 $task->rollback
 *                  "skipPending" => true,      是否跳过 $task->pending 全局结果判定方法，强制成功
 *                  "extraParams" => [...],     要传给当前步骤 handler 方法的 额外参数
 *              ]
 *      1.2     接口内部使用相同的 Task 创建代码，根据 post[taskId] 从持久化的缓存文件中恢复 Task 实例
 *              !! Task 实例的状态会被替换为 缓存文件中的状态数据，所有 closure 闭包方法使用创建代码中的定义
 *      1.3     首先检查 Task 任务层级的 status 状态
 *              1.3.1   $task->status === running 表示一切正常，执行步骤操作：
 *                      检查 $task->stepTo 取出对应的 TaskStep 步骤实例，检查 $taskStep->status 状态
 *                      1.3.1.1     $taskStep->status === running 表示一切正常
 *                                  执行 $taskStep->handler|pending 方法，进行实际业务操作
 *                                  方法内最终会修改 $taskStep->status = success|error
 *                                  通常在 pending 方法中修改 status
 *                                  !! 如果未定义 pending 方法，则必须在 handler 方法中修改 status
 *                                  !! TaskStep 实例必须定义 handler 业务方法
 *                                  handler|pending 方法执行完成后：
 *                                  1.3.1.1.1   未发生错误，检查 $taskStep->status：
 *                                              1.3.1.1.1.1     如果是 success 表示步骤操作成功，设置状态：
 *                                                              如果不是最后一步：
 *                                                                  $task->stepTo += 1
 *                                                                  设置下一步骤状态 $task->step[$task->stepTo]->status = running
 *                                                              执行持久化 $task->persist()
 *                                                              将完整状态数据返回前端，结束本次接口响应
 *                                              1.3.1.1.1.2     如果状态是 error 表示本步骤执行失败
 *                                                              手动创建一个业务错误，然后跳到 1.3.1.1.2
 *                                              1.3.1.1.1.3     如果状态是 running 表示在步骤执行中未修改 status 状态
 *                                                              !! 这是一个错误！手动创建 状态错误，然后跳转 1.3.1.1.2
 *                                  1.3.1.1.2   发生错误，或者手动创建了错误，设置状态：
 *                                              $taskStep->status = error
 *                                              执行持久化 $task->persist()
 *                                              将完整状态数据返回前端（附加 错误信息），结束本次接口响应
 *                                              返回数据结构：[
 *                                                  ... 完整状态数据,
 *                                                  "exceptions" => [ ... 错误信息 .... ],
 *                                                  !! 专门返回可跳过的步骤序号
 *                                                  "canSkipStep" => 当前步骤序号
 *                                              ]
 *                                              !! 前端会暂停轮询，用户查看到错误信息
 *                                              !! 有机会决定是否跳过此步骤
 *                                              !! 如果要跳过此步骤，只需要在下一次访问 next 接口时，post 传入
 *                                              !!      skipStep = 步骤序号
 *                      1.3.1.2     $taskStep->status === error 表示当前步骤已执行过，且失败了，执行 rollback 回滚
 *                                  设置状态数据：
 *                                      $task->status = rollbacking
 *                                      $task->rollbackTo = $task->stepTo
 *                                  执行当前步骤的 rollback 方法（如果存在的话），判断是否发生错误：（未定义 rollback 直接成功）
 *                                  1.3.1.2.1   rollback 方法执行成功，或未定义 rollback 方法
 *                                              修改状态：
 *                                                  $taskStep->status = rollbacked
 *                                              如果当前步骤不是第一步，则还要设置状态：
 *                                                  $task->rollbackTo -= 1
 *                                              执行持久化 $task->persist()
 *                                              将完整状态数据返回前端，结束本次接口响应
 *                                  1.3.1.2.2   rollback 方法执行失败，
 *                                              修改状态：
 *                                                  $taskStep->status = rollback-error
 *                                              执行持久化 $task->persist()
 *                                              将完整状态数据返回前端（附加 错误信息），结束本次接口响应
 *                                              返回数据结构：[
 *                                                  ... 完整状态数据,
 *                                                  "exceptions" => [ ... 错误信息 .... ],
 *                                                  !! 专门返回可跳过 rollback 的步骤序号
 *                                                  "canSkipRollback" => 当前步骤序号
 *                                              ]
 *                                              !! 前端会暂停轮询，用户查看到错误信息
 *                                              !! 有机会决定是否跳过此步骤的 rollback
 *                                              !! 如果要跳过此步骤的 rollback，只需要在下一次访问 next 接口时，post 传入
 *                                              !!      skipRollback = 步骤序号
 *                      1.3.1.3     $taskStep->status === success 表示当前步骤已经成功结束，且已是最后一步
 *                                  执行任务级 pending 结果判定方法（如果存在的话）
 *                                  !! 任务级结果判断 $task->pending() 方法内部如果判定为任务失败，则修改 $task->status = error
 *                                  先判断 pending 方法执行是否发生错误：（未定义 pending 方法跳过此步骤）
 *                                  1.3.1.3.1   $task->pending() 执行成功，或未定义 
 *                                              再判断 $task->status 任务状态：
 *                                              1.3.1.3.1.1     success 表示任务成功
 *                                                              删除任务缓存文件
 *                                                              将最后一步的 return 数据填充到 $task->return
 *                                                              将完整状态数据返回前端，完成整个 Task 任务的执行流程
 *                                                              !! 任务正常执行成功 并 成功返回结果
 *                                              1.3.1.3.1.2     error 表示任务被判定为失败，执行 rollback 回滚，修改状态：
 *                                                                  $task->status = rollbacking
 *                                                                  $task->rollbackTo = $task->stepTo
 *                                                              执行持久化 $task->persist()
 *                                                              将完整状态数据返回前端（附加 手动创建的 判定错误信息）
 *                                                              返回数据结构：[
 *                                                                  ... 完整状态数据,
 *                                                                  "exceptions" => [ ... 错误信息 .... ],
 *                                                                  !! 专门返回可跳过 pending 标记
 *                                                                  "canSkipPending" => true
 *                                                              ]
 *                                                              结束本次接口响应
 *                                                              !! 前端会暂停轮询，用户查看到错误信息
 *                                                              !! 知道这是 全局 pending 方法判定任务失败的
 *                                                              !! 实际上所有步骤都执行成功了
 *                                                              !! 有机会决定是否强制成功（因为所有步骤都执行成功了）
 *                                                              !! 如果要强制成功，只需要在下一次访问 next 接口时，post 传入
 *                                                              !!      skipPending = true
 *                                  1.3.1.3.2   $task->pending() 执行中发生错误
 *                                              修改状态：
 *                                                  $task->status = pending
 *                                              执行持久化 $task->persist()
 *                                              将完整状态数据返回前端（附加 错误信息），结束本次接口响应
 *                                              返回数据结构：[
 *                                                  ... 完整状态数据,
 *                                                  "exceptions" => [ ... 错误信息 .... ],
 *                                                  !! 专门返回可跳过 pending 标记
 *                                                  "canSkipPending" => true
 *                                              ]
 *                                              !! 前端会暂停轮询，用户查看到错误信息
 *                                              !! 有机会决定是否跳过全局 pending 强制成功（因为所有步骤都执行成功了）
 *                                              !! 如果要强制成功，只需要在下一次访问 next 接口时，post 传入
 *                                              !!      skipPending = true
 *                      1.3.1.4     $taskStep->status === skipped 表示这个步骤被跳过，判断是否最后一步：
 *                                  1.3.1.4.1   已是最后一步，跳到 1.3.1.3
 *                                  1.3.1.4.2   不是最后一步，设置状态：
 *                                              $task->stepTo += 1
 *                                              获取对应的 下一步骤，再次执行 1.3.1 步骤
 *                      1.3.1.5     $taskStep->status === rollbacked | rollback-error 
 *                                  !! 这是状态错误，通常不可能，设置新状态：
 *                                      $task->status = rollbacking
 *                                  重新执行 1.3 步骤
 * 
 * !!                   1.3.1.6     在 $task->status === running 情况下，如果额外传入了 skipStep 信息：
 *                                  !! 检查必须条件 post["skipStep"] === true || $task->stepTo === post["skipStep"]
 *                                  获取对应 TaskStep 步骤，设置状态：
 *                                      $taskStep->status = skipped
 *                                  设置 Task 任务状态：
 *                                      $task->stepTo += 1
 *                                  执行持久化 $task->persist()
 *                                  将完整状态数据返回前端，结束本次接口响应
 * !!                   1.3.1.7     在 $task->status === running 情况下，如果额外传入了 skipPending 信息：
 *                                  !! 表示所有步骤已经执行结束，只是最后的 全局 pending 方法判定任务失败了
 *                                  !! 检查已是最后一步，根据 skipPending 的值：
 *                                  1.3.1.7.1   true 表示跳过 全局 pending
 *                                              跳转 1.3.1.3.1.1 完成任务，返回结果，清除缓存
 *                                  1.3.1.7.2   false 表示不跳过全局 pending，pending 判定任务失败，则进入 rollback 回滚
 *                                              设置状态：
 *                                                  $task->status = rollbacking
 *                                                  $task->rollbackTo = $task->stepTo
 *                                               跳转 1.3.1.2 
 *              1.3.2   状态为 error 表示当前任务已经执行完毕，且已失败
 *                      !! 通常不可能，因为任务失败后，前端会终止轮询，不会再次进入接口
 *                      直接返回 任务已失败 错误给前端，前端自行终止后续请求
 * 
 *              1.3.3   状态为 rollbacking 表示当前任务正在 rollback 回滚
 *                      读取 $task->rollbackTo 序号，获取对应的 TaskStep 步骤实例，检查步骤状态：
 *                      1.3.3.1     $taskStep->status === error | success 表示此步骤还未执行过 rollback 回滚，
 *                                  跳转 1.3.1.2 执行此步骤的 rollback 方法，根据执行结果，决定后续返回
 *                      1.3.3.2     $taskStep->status === rollbacked 表示此步骤已经执行过 rollback 操作，
 *                                  !! 且已 rollback 到第一步，需要检查 $taskStep->idx === 0
 *                                  1.3.3.2.1   不是第一步，重新设置状态：
 *                                                  $task->rollbackTo -= 1
 *                                              获取对应的步骤实例，重新执行 1.3.3
 *                                  1.3.3.2.2   如果已是第一步：
 *                                              尝试执行 任务级 rollback 全局方法（如果有的话）
 *                                              判断是否发生错误：（未定义视为执行成功）
 *                                              1.3.3.2.2.1     未发生错误 或 未定义全局 rollback
 *                                                              设置状态：
 *                                                                  $task->status = error
 *                                                              删除任务缓存文件
 *                                                              将最后一步的 return 数据填充到 $task->return
 *                                                              将完整状态数据返回前端，完成整个 Task 任务的执行流程
 *                                                              !! 任务执行失败后回滚成功 并 成功返回结果
 *                                              1.3.3.2.2.2     发生错误
 *                                                              执行持久化 $task->persist()
 *                                                              将完整状态数据返回前端（附加 手动创建的 全局回滚错误信息）
 *                                                              返回数据结构：[
 *                                                                  ... 完整状态数据,
 *                                                                  "exceptions" => [ ... 错误信息 .... ],
 *                                                                  !! 专门返回全局回滚错误 标记
 *                                                                  "taskRollbackError" => true
 *                                                              ]
 *                                                              结束本次接口响应
 *                                                              !! 前端会暂停轮询，用户查看到错误信息
 *                                                              !! 知道这是 全局 rollback 方法执行失败
 *                                                              !! 所有步骤回滚方法都已成功执行了
 *                                                              !! 有机会决定是否强制跳过全局回滚
 *                                                              !! 如果要强制跳过全局回滚，只需要在下一次访问 next 接口时，post 传入
 *                                                              !!      skipTaskRollback = true
 *                                                              
 * !!                   1.3.3.3     在 $task->status === rollbacking 情况下，如果传入了额外的 skipTaskRollback 标记
 *                                  跳转到 1.3.3.2.2.1 返回任务结果，删除缓存，结束任务
 *              1.3.4   状态为 pending 表示任务所有步骤已经成功执行，只是在执行全局 pending 方法时出错
 *                      检查是否传入了 skipPending 标记
 *                      1.3.4.1     传入了 skipPending 标记，转到 1.3.1.3.1.1 完成任务，返回结果，删除缓存
 *                      1.3.4.2     未传入 skip 标记，表示确认任务失败，执行 rollback 回滚，修改状态：
 *                                  $task->status = rollbacking
 *                                  $task->rollbackTo = $task->stepTo
 *                                  跳转 1.3.1.2 
 *                                  
 *  2   status 接口
 *      前端访问 /api/some_task/status 必须 post taskId 
 *      直接返回 当前 任务实例的完整状态
 * 
 *  3   stop 接口
 *      前端访问 /api/some_task/stop 必须 post taskId 
 *      接口内部使用相同的 Task 创建代码，根据 post[taskId] 从持久化的缓存文件中恢复 Task 实例
 *      设置状态：
 *          $task->status = rollbacking
 *          $task->rollbackTo = $task->stepTo
 *          跳转 1.3.1.2 开始任务回滚
 *          !! 前端需要再次 轮询 next 接口，以完成任务回滚，最终结束任务，删除缓存
 *      
 *                  创建 Task 实例，
 *  0   创建 Task 实例：前端访问 task/create 接口，执行 Task::define("create", [...params]) 方法：
 *          生成 $task->id 全局唯一
 *          创建 $task->status = "created"
 *          创建 $task->stepTo = -1
 *          创建 $task->timestamp["start" => 0, "end" => 0]
 *          创建 $task->pending = function(): void {
 *              !! 可选的，用来判断整个 Task 任务是否成功的 方法
 *              !! $this 指向当前 $task 任务实例
 *              !! 修改 $this->status = "success|error" 表示成功或失败
 *              !!      失败时一定要设置 $this->setStatus("error")
 *              !!      成功时可以不设置 success
 *              !! 不返回数据
 *          }
 *          创建 $task->rollback = function(): void {
 *              !! 可选的，Task 任务全局 回滚方法
 *              !! 在所有 step 步骤自身的 rollback 回滚方法执行完成后，才会执行此方法，属于最终兜底
 *              !! $this 指向当前 $task 任务实例
 *              !! 如果要修改整个 Task 任务的最终返回值，只需要修改最后一步的 return 数据
 *              !!      $this->getLastStep()->return = [...]
 *              !! 不返回数据
 *          }
 *          创建 $task->step[]，通过实例方法 $task->addStep(
 *              [
 *                  "idx" => 自动序号 0,1...
 *                  "title" => "步骤名称",
 *                  "key" => "可选步骤唯一 key，可以通过 key 访问步骤",
 *                  "status" => "waiting | running | success | error",
 *                  "arguments" => [
 *                      !! 传递给本步骤的参数
 *                      "result" => [
 *                          !! 上一步骤的执行结果
 *                          ...
 *                      ],
 *                      ...
 *                  ],
 *                  "return" => [
 *                      !! 本步骤操作的执行结果
 *                      ...
 *                  ],
 *                  "timestamp" => [
 *                      "start" => 123,
 *                      "end" => 0,     状态变为 success|error 都视为 end 结束
 *                  ],
 *                  "handler" => function(): void {
 *                      !! 实际操作的执行方法
 *                      !! 在此方法内部：
 *                      !!      $this 指向当前步骤实例
 *                      !!      $this->task 指向当前 Task 实例
 *                      !!      读取 $this->arguments
 *                      !!      修改 $this->timestamp["start"]
 *                      !!      修改 $this->return
 *                      !! 不返回数据
 *                      ...
 *                  },
 *                  "pending" => function(): void {
 *                      !! 可选的，对 handler 方法执行结果进行 success|error 判定
 *                      !!      如果不指定此方法，则必须在 handler 方法内部 $this->setStatus("success|error")
 *                      !! 修改 $this->setStatus("success|error")
 *                      !! 不返回数据
 *                  },
 *                  "rollback" => function(): void {
 *                      !! 可选的，步骤失败时的 回滚方法
 *                      !! 如果定义了此方法，则会优先执行，完成后才会执行在 step 外部定义的 $task->rollback() 统一回滚方法
 *                      !! 此方法内执行回滚操作，如果要修改最终返回值，直接修改 $this->return 数据即可
 *                      !! 不返回数据
 *                  }
 *              ]
 *          )
 * 
 *      !! 将创建好的 Task 实例执行 $task->persist() 持久化，方法是：
 *          创建缓存 [taskid].json 文件，记录 Task 实例的当前完整的状态
 *          !! 要求 step[] 中不能是类实例，只能是可序列化的数据
 *          !! closure 无法被持久化，在从缓存恢复 Task 实例时，会重新执行 $task->addStep() 会恢复 closure
 * 
 *      Task 实例创建完成后，返回数据给前端：
 *          return [
 *              "id" => $task->id,
 *              "status" => $task->status,
 *              "return" => [
 *                  !! 返回写入缓存文件的 Task 完整状态数据
 *              ]
 *          ]
 * 
 *  1   由前端轮询 此 Task 的 next 接口，再次执行 Task::define("next", ...) 方法，执行操作：
 *      根据 post["taskId"] 读取 缓存文件，取得 task 状态，恢复 Task 的 id,status,timestamp,stepTo 等缓存的状态，
 *      针对 step 各步骤的状态，因为访问的接口仅 action 发生改变，各步骤的 addStep() 方法调用仍旧存在，因此可以：
 *          替换 addStep([]) 方法参数中的 各步骤的状态，替换为从 cache 中恢复的，
 *              各步骤的 closure 方法，仍旧使用原始的定义，恢复即可
 * 
 *      读取 step 步骤状态，执行新步骤，修改状态
 *      !! 先判断上一步 step 是否发生错误，如果发生错误，则执行回滚操作，将最终状态返回前端，前端将会终止轮询，Task 终止
 *      !! 如果上一步 未发生错误，则执行下一步 step 的 handler 方法，修改状态
 *      !! 不论当前步骤是否成功，一定执行 $task->persist() 将新状态持久化，然后结束本次轮询响应，返回新状态
 *      !! 下一次轮询到来时，才会判断 step 是否出错
 *      !! 每一次轮询，会执行一个 step ，然后返回完整的 Task 状态给前端
 *      !!      或者执行回滚，返回最终状态给前端，前端终止轮询，Task 任务终止
 *      !! 当执行回滚，或所有 step 都成功执行后，都会向前端返回 Task 的最终状态
 *      !!      一旦最终状态生成，将清除 缓存文件
 * 
 * 使用方法：
 * 
 *  0   在接口中使用：
 * 
 *      public function someTaskApi(...$args) {
 *          !! 需要先接收 接口路径参数 $args[0]，作为 action 参数
 *          !! 可选的 action ：
 *          !!      create      $args 为空时默认的，首次调用的接口，创建 Task 
 *          !!      next        依次执行 Task 各 step 步骤的 handler 方法，需要 post["taskId"]
 *          !!      stop        前端可以手动终止 Task，需要 post["taskId"]
 *          !!      status      可以手动读取 Task 任务的当前状态，需要 post["taskId"]
 *          $action = $args[0] ?? "create";
 * 
 *          !! 在接口内部定义一个 Task 任务，如果 action!=="create" 则是从缓存中恢复 Task 实例
 *          $task = Task::define(
 *              $action, 
 *              
 *              !! 接口内调用已有的 Task，通过 post 传入 taskId，define 方法会自行从 Request->posts 中获取
 *              !! 因此此处不需要手动传入
 *              null,
 *              
 *              !! 定义 Task 初始参数
 *              [
 *                  "title" => "Task 任务标题",
 *                  "pending" => function() {
 *                      ...可选的 Task 任务完成性判断方法
 *                  },
 *                  "rollback" => function() {
 *                      ...可选的统一回滚方法
 *                  },
 * 
 *                  !! 可以在此定义各 step 步骤的参数和 closure
 *                  "step" => [
 *                      [
 *                          "title" => "步骤标题",
 *                          "key" => "步骤唯一 key，可选的，除了 stepIdx 还可以通过此 key 访问步骤信息",
 *                          "arguments" => [
 *                              ...定义此步骤的可选的 初始参数
 *                          ],
 *                          !! 定义此步骤的 closure
 *                          "handler" => function() { ... },
 *                          "pending" => function() { ... },
 *                          "rollback" => function() { ... },
 *                      ],
 *                      ...
 *                  ]
 *              ]
 *          );
 * 
 *          !! 更推荐使用实例方法定义 step 步骤
 *          $task->addStep([
 *              "title" => "..",
 *              "key" => "..",
 *              ...,
 *              "handler" => function() { ... },
 *              "pending" => function() { ... },
 *              "rollback" => function() { ... },
 *          ]);
 *          $task->addStep([...]);
 *          ...
 * 
 *          !! 可以循环定义 step
 *          for ($i=0;$i<n;$i++) {
 *              $task->addStep([...]);
 *          }
 * 
 *          !! Task 实例 定义|恢复 完成后，只要再次调用 next 接口，Task 实例内部会自行依次调用各步骤，返回结果
 *          !! 接口内部只需要调用实例方法：
 *          return $task->return($action);
 *          
 *      }
 * 
 *  1   在任意方法内部使用：
 *      !! 不推荐此种调用方法，因为失去了 长耗时任务分步执行的原始意义
 * 
 *      !! 定义 Task
 *      public function someTask($action, $taskId = null) {
 *          !! 与接口内的定义方法基本一致
 *          !! 仅在 define() 方法中，传入 $taskId 作为第二参数
 *          $task = Task::define(
 *              $action, 
 *              $taskId,
 *              [
 *                  ...Task 初始参数
 *              ]
 *          );
 *          !! 实例方法新增 step
 *          $task->addStep([...]);
 *          ...
 * 
 *          !! 执行 Task 返回结果
 *          return $task->return($action)
 *      }
 * 
 *      !! 其他方法内部调用此 Task
 *      public function someMethod() {
 *          ...
 * 
 *          $task = $this->someTask("create");
 *          $taskId = $task["id"];
 *          
 *          !! 开始依次执行
 *          $task = $this->someTask("next", $taskId);
 *          while (!in_array($task["status"], ["success", "error"])) {
 *              $task = $this->someTask("next", $taskId);
 *          }
 * 
 *          !! 报错
 *          if ($task["status"] === "error") {
 *              throw Exception(...);
 *              return
 *          }
 *          
 *          ...
 *      }
 * 
 * !! 关于 Task->locked 任务锁
 * 这个 Task 任务类设计用途主要是处理长耗时接口任务，对应的前端通常是在一个 异步弹窗组件 内完成所有步骤
 * 从 任务创建，记录 taskId，轮询，进度条滚动显示，错误处理，取得最终结果，
 * 直到这个异步弹窗组件向外部 resolve 最终接口返回值，组件生命周期结束。
 * 所有操作都是在这个组件生命周期内，taskId 通常不会泄露到外部，也就不会有其他用户组件获取并同时调用这个 长耗时任务接口
 * Spf 框架配套的前端组件库包含 DvTask 配套组件，所有的接口调用，轮询，进度条，错误显示，重试|取消按钮都已封装好，前端只需要：
 *      <script lang="ts">
 *          
 *          const requestTask = async (): Promise<any> => {
 *              const result = await dvWinService.openAsync(
 *                  'DvTask',
 *                  {
 *                      api: '/api/test_task'
 *                  }
 *              )
 *              ...获取到的接口最终返回值在 result.return 中
 *              ...所有 Task 任务本身，以及各 step 最终状态，都在 result 中
 *          }
 *          
 *      </script>
 * 因此，任务锁暂时不需要
 */

namespace Spf\util;

use Spf\Core;
use Spf\App;
use Spf\Request;
use Spf\exception\BaseException;
use Spf\exception\AppException;

class Task extends SpecialUtil 
{
    /**
     * 此工具 在启动参数中的 参数定义
     *  [
     *      "util" => [
     *          "util_name" => [
     *              # 如需开启某个 特殊工具，设为 true
     *              "enable" => true|false, 是否启用
     *              ... 其他参数
     *          ],
     *      ]
     *  ]
     * !! 覆盖父类静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = true;
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];

    //Task 缓存文件位置，默认在网站根目录 runtime/task/... 路径下
    //!! 可在 index.php 中的框架启动参数 env["util"]["task"]["cachePath"] 参数中修改
    public static $cachePath = "runtime/task";

    //状态数据结构，需要被持久化的 数据结构
    //Task 任务状态数据结构
    public static $stdTask = [
        "id" => "",
        "title" => "未命名任务",
        "status" => "created",  //created|running|pending|success|rollbacking|error
        "stepTo" => -1,
        "rollbackTo" => -1,
        "timestamp" => [
            "start" => 0,
            "end" => 0,
        ],
        "step" => [
            //保存各步骤的状态数据[]
        ],
    ];
    //Task step 任务步骤状态数据结构
    public static $stdTaskStep = [
        "idx" => -1,
        "title" => "未命名步骤",
        "key" => "",
        "status" => "waiting",  //waiting|running|success|error
        "arguments" => [
            "result" => [],
        ],
        "return" => [],
        "timestamp" => [
            "start" => 0,
            "end" => 0,
        ],
    ];

    /**
     * 静态方法
     */

    /**
     * 创建 全局唯一的 taskId
     * @return String 全局唯一 taskId
     */
    public static function createTaskId()
    {
        $idps = [];
        //如果存在当前应用
        if (App::$isInsed === true) {
            $appk = App::$current->name;
            $idps[] = $appk;
        }
        //时间戳
        $idps[] = date("YmdHis", time());
        //随机字符串 md5
        $idps[] = md5(Str::nonce(16));
        //id
        $id = "task_".implode("_", $idps);
        //查找是否存在文件
        $fp = static::$cachePath."/".$id.".json";
        //如果存在文件，则重新生成
        if (is_null(Path::find($fp, Path::FIND_FILE))) return $id;
        return static::createTaskId();
    }

    /**
     * 根据 taskId 读取缓存文件，返回 缓存的数据
     * @param String $taskId
     * @return Array|null 缓存的 Task 实例状态
     */
    public static function getCacheByTaskId($taskId)
    {
        if (!Is::nemstr($taskId)) return null;
        $fp = Path::find(static::$cachePath."/".$taskId.".json", Path::FIND_FILE);
        if (!file_exists($fp)) return null;
        //读取
        return Conv::j2a(file_get_contents($fp));
    }

    /**
     * 根据 taskId 删除缓存文件
     * @param String $taskId
     * @return Bool
     */
    protected static function delCacheByTaskId($taskId)
    {
        if (!Is::nemstr($taskId)) return false;
        $fp = Path::find(static::$cachePath."/".$taskId.".json", Path::FIND_FILE);
        //已经被删除了
        if (!file_exists($fp)) return true;
        return unlink($fp);
    }

    /**
     * 判断是否存在 Task 任务
     * @param String $taskId
     * @return Bool
     */
    public static function exists($taskId)
    {
        $cache = static::getCacheByTaskId($taskId);
        return Is::nemaso($cache);
    }

    /**
     * 新建|恢复 Task 实例 工厂方法
     * @param String $action 当前 Task 是在 什么接口中被 创建|恢复，可选 create|next|status|stop...
     * @param String|null $taskId 当前 Task 的 id，next 接口必须，不传则尝试从 Request->inputs->json 中读取
     * @param Array $params Task 任务初始参数，与 $stdTask 结构一致
     * @return Task|null 实例
     */
    public static function define($action, $taskId=null, $params=[])
    {
        if (!Is::nemaso($params)) $params = [];

        //根据 $action 决定 $params 来源
        if ($action !== "create") {
            //!! 恢复 Task
            if (!Is::nemstr($taskId)) {
                //未传入 taskId 则尝试从 Request 实例中获取
                if (Request::$isInsed !== true) {
                    throw new AppException("无法调用 Task ，缺少必须的 taskId", "app/response");
                    return null;
                }
                $taskId = Request::$current->inputs->json["taskId"] ?? null;
                if (!Is::nemstr($taskId)) {
                    throw new AppException("无法调用 Task ，缺少必须的 taskId", "app/response");
                    return null;
                }
            }
            $cache = static::getCacheByTaskId($taskId);
            if (!Is::nemaso($cache)) {
                throw new AppException("无法调用 Task ，未找到缓存文件 $taskId", "app/response");
                return null;
            }
            //使用 cache 数据覆盖 传入的 $params
            foreach (static::$stdTask as $k => $v) {
                if (!isset($cache[$k]) || $k === "step") continue;
                $params[$k] = $cache[$k];
            }
            //!! 恢复 locked 锁定标记
            if (isset($cache["locked"])) $params["locked"] = $cache["locked"];
            //单独处理 step
            if (isset($params["step"])) {
                for ($i=0;$i<count($params["step"]);$i++) {
                    if (!isset($cache["step"]) || !isset($cache["step"][$i])) continue;
                    foreach (static::$stdTaskStep as $ik => $iv) {
                        if (!isset($cache["step"][$i][$ik])) continue;
                        $params["step"][$i][$ik] = $cache["step"][$i][$ik];
                    }
                }
            }

            //恢复 taskId
            $params["id"] = $taskId;
        } else {
            //!! 新建 Task
            if (!Is::nemaso($params)) {
                throw new AppException("无法创建 Task ，缺少必要的初始参数", "app/response");
                return null;
            }
            
            //创建 taskId
            $taskId = static::createTaskId();
            $params["id"] = $taskId;

            //使用默认值填充
            foreach (static::$stdTask as $k => $v) {
                if (isset($params[$k]) || $k === "step") continue;
                $params[$k] = is_scalar($v) ? $v : Arr::copy($v);
            }
            //单独处理 step
            if (isset($params["step"])) {
                for ($i=0;$i<count($params["step"]);$i++) {
                    foreach (static::$stdTaskStep as $ik => $iv) {
                        if (isset($params["step"][$i][$ik])) continue;
                        $params["step"][$i][$ik] = is_scalar($iv) ? $iv : Arr::copy($iv);
                    }
                }
            }
        }

        //构造
        $task = new Task();
        //填充属性
        foreach (static::$stdTask as $k => $v) {
            if (!isset($params[$k]) || $k === "step") continue;
            $task->$k = $params[$k];
        }
        //task->pending|rollback 方法
        $closures = ["pending", "rollback"];
        foreach ($closures as $fn) {
            if (isset($params[$fn]) && is_callable($params[$fn])) {
                $task->$fn = $params[$fn];
            }
        }
        
        //单独处理 step
        if (isset($params["step"])) {
            for ($i=0;$i<count($params["step"]);$i++) {
                $params["step"][$i]["idx"] = $i;
                //!! 创建 TaskStep 实例
                $task->step[] = new TaskStep($task, $params["step"][$i]);
            }
        }
        //缓存 action
        $task->action = $action;

        return $task;
    }



    /**
     * Task 实例
     */
    public $id = "";
    //Task 任务标题
    public $title = "";
    //状态
    public $status = "created"; //created|running|pending|success|rollbacking|error
    public $stepTo = -1;
    public $rollbackTo = -1;
    public $timestamp = [
        "start" => 0,
        "end" => 0,
    ];
    //step 步骤
    public $step = [];

    //!! 当前任务是否已被锁定
    public $locked = false;

    //!! 缓存当前 Task 所在接口的 action
    public $action = "create";

    //closure 闭包方法
    public $pending = null;
    public $rollback = null;

    /**
     * 设置 status|timestamp
     * @return $this
     */
    public function setStatus($status)
    {
        if (!Is::nemstr($status) || !in_array($status, ["waiting","running","pending","success","rollbacking","error"])) return $this;
        $this->status = $status;
        return $this;
    }
    public function setTimestamp($key = "start", $t = 0)
    {
        $this->timestamp[$key] = $t > 0 ? $t : Dater::microtime();
        return $this;
    }

    /**
     * 生成当前 Task 的 完整状态数据
     * @param Array $extra 要附加到返回数据中的 额外数据，使用 Arr::extend
     * @return Array
     */
    public function getFullStatus($extra = [])
    {
        $status = [];
        foreach (static::$stdTask as $k => $v) {
            if ($k === "step") continue;
            if (!isset($this->$k)) {
                $status[$k] = $v;
            } else {
                $status[$k] = $this->$k;
            }
        }
        //单独处理 step
        $status["step"] = [];
        for ($i=0;$i<count($this->step);$i++) {
            $status["step"][$i] = [];
            foreach (static::$stdTaskStep as $ik => $iv) {
                if (!isset($this->step[$i]->$ik)) {
                    $status["step"][$i][$ik] = $iv;
                } else {
                    $status["step"][$i][$ik] = $this->step[$i]->$ik;
                }
            }
        }

        //extra
        if (Is::nemaso($extra)) {
            //数组使用覆盖方法，而不是合并去重
            $status = Arr::extend($status, $extra, true);
        }
        
        return $status;
    }

    /**
     * 持久化到缓存文件
     * !! 仅持久化 Task 实例的状态数据，必须是可以被序列化的数据
     * @param Array $extra 额外要持久化的 数据
     * @return Bool
     */
    public function persist($extra = [])
    {
        $cp = Path::find(static::$cachePath, Path::FIND_DIR);
        if (empty($cp)) return false;
        $fp = $cp.DS.$this->id.".json";

        //要持久化的数据
        $cache = $this->getFullStatus($extra);

        //写入缓存文件
        return Path::mkfile($fp, Conv::a2j($cache));
    }

    /**
     * 锁住|解锁 Task 任务
     * @param String $taskId
     * @return Bool
     */
    public function lock()
    {
        if ($this->locked) return true;
        //向持久化数据中写入 锁定标记
        $pok = $this->persist([
            "locked" => true
        ]);
        if ($pok === true) {
            $this->locked = true;
            return true;
        }
        return false;
    }
    public function unlock()
    {
        if (!$this->locked) return true;
        //向持久化数据中写入 锁定标记
        $pok = $this->persist([
            "locked" => false
        ]);
        if ($pok === true) {
            $this->locked = false;
            return true;
        }
        return false;
    }

    /**
     * 创建 step 步骤
     * @param Array $step 步骤参数，与 $stdTaskStep 结构一致
     * @return $this
     */
    public function addStep($step=[])
    {
        if (!Is::nemaso($step)) $step = [];

        //要创建步骤的 idx 
        $idx = count($this->step);

        //!! 恢复 Task
        if ($this->action !== "create") {
            //读取缓存状态
            $cache = static::getCacheByTaskId($this->id);
            if (!Is::nemaso($cache) || !isset($cache["step"]) || !isset($cache["step"][$idx])) {
                throw new AppException("无法调用 Task ，未找到缓存文件 $this->id", "app/response");
                return $this;
            }
            //使用缓存的状态覆盖 传入的参数
            foreach (static::$stdTaskStep as $k => $v) {
                if (!isset($cache["step"][$idx][$k])) continue;
                $step[$k] = $cache["step"][$idx][$k];
            }
        }

        //!! 新建 Task
        else {
            if (!Is::nemaso($step)) {
                throw new AppException("无法创建 Task ，缺少必要的初始参数", "app/response");
                return $this;
            }
            foreach (static::$stdTaskStep as $k => $v) {
                if (isset($step[$k])) continue;
                $step[$k] = is_scalar($v) ? $v : Arr::copy($v);
            }
            //写入 idx
            $step["idx"] = $idx;
        }

        //创建 步骤实例，写入 $this->step[]
        $this->step[] = new TaskStep($this, $step);

        return $this;
    }

    /**
     * 判断是否不包含任何 step 步骤
     * @return Bool
     */
    public function noStep()
    {
        return count($this->step) <= 0;
    }

    /**
     * 根据 stepIdx|Key 获取 step object
     * @param Number|String $idx 步骤 idx 或 key
     * @return Object|null 返回 步骤 object 引用
     */
    public function getStep($idx=0)
    {
        if ($this->noStep()) return null;

        //根据 序号
        if (is_numeric($idx)) {
            $idx = $idx * 1;
            $step = $this->step[$idx] ?? null;
            return !empty($step) ? $step : null;
        }

        //根据 stepKey
        for ($i=0;$i<count($this->step);$i++) {
            if (isset($this->step[$i]->key) && $this->step[$i]->key === $idx) {
                return $this->step[$i];
            }
        }
        return null;
    }

    /**
     * 获取最后一步 step object
     * @return Object|null 返回 步骤 object 引用
     */
    public function getLastStep()
    {
        if ($this->noStep()) return null;
        return $this->step[count($this->step) - 1];
    }

    /**
     * __call 调用为 Task 实例动态添加的 pending|rollback 闭包方法
     */
    public function __call($method, $args)
    {
        if (!isset($this->$method)) return null;

        //查找闭包方法
        $closure = $this->$method;
        if ($closure instanceof \Closure) {
            //绑定 $this
            $closure = \Closure::bind($closure, $this, get_class($this));
            return $closure(...$args);
        }

        return null;
    }

    /**
     * 执行 step 步骤
     * @param String|null $action 当前 Task 所在接口 action，不传则使用缓存的 $task->action
     * @return Array|null 将返回给前端的 Task 当前完整状态
     */
    final public function return($action=null)
    {
        if ($this->noStep()) {
            throw new AppException("无法执行 Task [ $this->id ]，没有任务步骤信息", "app/response");
            return null;
        }

        if (!Is::nemstr($action)) $action = $this->action;

        //!! 新建 Task
        if ($action === "create") {
            //持久化
            if ($this->persist() !== true) {
                return $this->executeRollback(
                    new AppException("持久化 Task [ $this->id ] 状态出错，已删除文件", "app/response")
                );
            }

            //直接返回当前 完整状态
            return $this->getFullStatus();
        }

        //!! 其他 非 next 接口
        switch ($action) {
            //status
            case "status": 
                return $this->getFullStatus();
                break;

            //stop
            case "stop":
                return $this->stepToEnd(true);
                break;
        }

        //!! next 接口

        //!! 如果当前 Task 状态为 pending，表示上一次 stepToEnd 失败了，直接进入 stepToEnd
        if ($this->status === "pending") {
            return $this->stepToEnd();
        }
        //!! 如果当前 Task 状态为 rollbacking，表示上一次 rollback 失败了，直接进入 rollback
        if ($this->status === "rollbacking") {
            return $this->executeRollback();
        }

        //获取上一步执行结果
        $lastOk = false;
        $lastStep = null;
        if ($this->stepTo < 0) {
            $lastOk = true;
        } else {
            $lastStep = $this->getStep($this->stepTo);
            if (empty($lastStep)) {
                return $this->executeRollback(
                    new AppException("无法执行 Task [ $this->id ]，无法获取上一步的执行结果", "app/response")
                );
            }
            $lastOk = $lastStep->status === "success";
        }

        //!! 如果发现上一步执行错误，触发回滚
        if ($lastOk !== true) {
            return $this->executeRollback();
        }

        //!! 如果当前已到最后一步(通常不可能，因为每一步执行完后，都会判断一次是否到最后一步)
        if ($lastStep instanceof TaskStep && $lastStep->isLastStep()) {
            //!! 触发 结束流程
            return $this->stepToEnd();
        }

        //开始执行下一步
        $this->stepTo += 1;
        if ($this->status === "created") {
            $this->setStatus("running");
            $this->setTimestamp("start");
        }
        //当前步骤
        $step = $this->getStep($this->stepTo);
        //!! 检查步骤参数
        if (!($step instanceof TaskStep && $step->isAvailable())) {
            return $this->executeRollback(
                new AppException("无法执行 Task [ $this->id ]，无法获取有效的步骤参数 [ 第 ".($this->stepTo + 1)." 步]", "app/response")
            );
        }
        //!! 检查步骤状态，步骤必须未完成
        if ($step->isEnd() === true) {
            return $this->executeRollback(
                new AppException("无法执行 Task [ $this->id ]，当前步骤状态不正确 [ 第 ".($this->stepTo + 1)." 步]", "app/response")
            );
        }
        //!! 修改步骤状态
        if (!$this->isRunning()) {
            $step->setStatus("running");
            $step->setTimestamp("start");
        }
        //执行此步骤
        try { 
            $step->handler();
            //判断当前步骤执行结果，修改步骤状态
            //!! 如果当前步骤未定义 pending 方法，则需要在 handler 方法中修改步骤状态
            $step->pending();
        } catch (BaseException $e) {
            //$step->handler 内部执行异常，触发回滚
            //!! BaseException 封装了所有 PHP 错误（包括 fatal error）
            //!! AppException 等业务异常是 BaseException 子类
            return $this->executeRollback($e);
        }
        //记录步骤完成时间
        if ($step->isEnd()) $step->setTimestamp("end");

        //持久化
        if ($this->persist() !== true) {
            return $this->executeRollback(
                new AppException("持久化 Task [ $this->id ] 状态出错，已删除文件", "app/response")
            );
        }

        //!! 每一步执行完成后，判断一次是否最后一步，且执行成功
        if ($step->isSuccess() && $step->isLastStep()) {
            //!! 触发 结束 流程
            return $this->stepToEnd();
        }

        //返回执行结果数据
        return $this->getFullStatus();
    }

    /**
     * 所有步骤都已成功执行，结束 Task ，并返回最终数据
     * @param Bool $manual 是否手动结束当前 Task
     * @return Array|null 返回前端的最终 Task 完整状态
     *               !! 如果 Task 成功结束，则最终结果数据在返回数据的 return 键值中
     */
    protected function stepToEnd($manual = false)
    {
        if ($this->noStep()) {
            throw new AppException("无法执行 Task [ $this->id ]，没有任务步骤信息", "app/response");
            return null;
        }

        //!! 手动结束，前端用户主动结束 Task ，应自动回滚，不论是否成功
        if ($manual) {
            //自动回滚
            $rtn = $this->executeRollback();
            //新增手动结束标记
            $rtn["manualStop"] = true;
            //返回
            return $rtn;
        }

        //最后一步的 status
        $lstep = $this->getLastStep();
        $isSuccess = empty($lstep) ? false : $lstep->isSuccess();

        //Task 标记 pending 状态
        $this->setStatus("pending");

        try {
            //!! 如果存在 Task 全局的 pending 方法，调用一次
            if (isset($this->pending)) {
                $this->pending();
                //!! pending 方法内部，通过修改 $this->status 来表示任务成功还是失败
                $isSuccess = $this->status !== "error";
            }
        } catch(BaseException $e) {
            //全局 pending 内部错误
            //持久化
            $this->persist();
            //返回状态，包含错误信息
            return $this->getFullStatus([
                "exceptions" => [$e->getInfo(true)]
            ]);
        }

        //如果最终判断 Task 任务失败，触发回滚
        if ($isSuccess !== true) {
            return $this->executeRollback(
                new AppException("Task [ $this->id ] 任务判定为执行失败", "app/response")
            );
        }

        //最终状态
        $this->setStatus("success");
        $this->setTimestamp("end");
        //删除缓存
        static::delCacheByTaskId($this->id);
        //最终状态
        return $this->getFullStatus([
            //!! 将最后一步的 return 数据添加到返回值的 return 中
            "return" => $this->getLastStep()->return
        ]);
    }

    /**
     * 任意步骤出错，或 Task 全局 pending 方法判定失败，执行回滚方法
     * @param BaseException $exception 导致 rollback 回滚的 exception，可以是任意 PHP|fatal|业务 错误
     * @return Array|null 返回前端的最终 Task 完整状态
     */
    protected function executeRollback($exception = null)
    {
        if ($this->noStep()) {
            throw new AppException("无法执行 Task [ $this->id ]，没有任务步骤信息", "app/response");
            return null;
        }

        //记录 exception info
        $exs = [];
        if ($exception instanceof BaseException) {
            $exs[] = $exception->getInfo(true);
        }

        //!! 优先调用各步骤自身的 rollback 方法
        //!! 反向执行各步骤的 rollback
        //先判断从哪一步开始 rollback
        $from = $this->status === "rollbacking" ? $this->rollbackTo : $this->stepTo;
        //再标记状态
        $this->setStatus("rollbacking");
        //从 $from 步骤开始 rollback
        if ($from >= 0) {
            //rollbackTo
            $rollbackTo = -1;
            //!! rollback 时，各步骤 return 数据会反向 extend ，各步骤的 rollback 方法内可以修改本步骤 return 数据
            $rbRtn = [];
            for ($i=$from;$i>=0;$i--) {
                $step = $this->getStep($i);
                if (empty($step)) continue;
                //执行
                try {
                    //如果本步骤有定义 rollback 方法
                    $step->rollback();
                    $rbRtn = Arr::extend($rbRtn, $step->return, true);
                } catch(BaseException $e) {
                    //$step->rollback 内部错误，记录 rollbackTo ，然后中断
                    $rollbackTo = $i;
                    //收集错误
                    $exs[] = $e->getInfo(true);
                    break;
                }
            }

            //如果 rollback 被中断
            if ($rollbackTo >= 0) {
                //记录
                $this->rollbackTo = $rollbackTo;
                //!! 记录当前已 rollback 的 return 数据，保存到 rollbackTo 步骤的 return 中
                $this->getStep($rollbackTo)->return = $rbRtn;
                //持久化
                $this->persist();
                //返回状态，包含错误信息
                return $this->getFullStatus([
                    "exceptions" => Is::nemarr($exs) ? $exs : []
                ]);
            } 
            //如果 rollback 未被中断
            else {
                //记录
                $this->rollbackTo = -1;
                //!! 将已被 rollback 的 return 数据写入最后一步 return 中
                //!! 因为后续可能要执行 Task 全局 rollback，可能需要修改最终返回数据
                $this->getLastStep()->return = $rbRtn;
            }
        }

        //如果存在 Task 全局 rollback，则尝试执行
        try {
            //!! 此时全局 return 值保存在最后一步的 return 中，与 stepToEnd 读取全局返回值行为一致
            //!! 全局 rollback 方法中如果要修改全局返回值，只需修改最后一步的 return 数据
            $this->rollback();
        } catch(BaseException $e) {
            //全局 rollback 内部错误
            $exs[] = $e->getInfo(true);
            //持久化
            $this->persist();
            //返回状态，包含错误信息
            return $this->getFullStatus([
                "exceptions" => Is::nemarr($exs) ? $exs : []
            ]);
        }

        //最终状态
        $this->setStatus("error");
        $this->setTimestamp("end");
        //删除缓存
        static::delCacheByTaskId($this->id);
        //返回数据，包含错误信息
        $extra = [
            //!! 将最后一步的 return 数据添加到全局返回值
            "return" => $this->getLastStep()->return
        ];
        //包含可能存在的 错误信息
        if (Is::nemarr($exs)) $extra["exceptions"] = $exs;
        return $this->getFullStatus($extra);
    }

}

/**
 * 定义 TaskStep 类
 */
class TaskStep 
{
    //步骤状态
    public $idx = -1;
    public $title = "未命名步骤";
    public $key = "";
    public $status = "waiting";  //waiting|running|success|error
    public $arguments = [
        "result" => [],
    ];
    public $return = [];
    public $timestamp = [
        "start" => 0,
        "end" => 0,
    ];

    //引用所在 Task 实例
    protected $task = null;

    //定义闭包
    public $handler = null;
    public $pending = null;
    public $rollback = null;

    /**
     * 构造
     * @param Array $params 步骤构造参数
     * @return void
     */
    public function __construct(Task $task, $params = [])
    {
        foreach ($params as $k => $v) {
            $this->$k = $v;
        }
        $this->task = $task;
    }

    /**
     * 通用的 TaskStep 工具方法
     */

    /**
     * 设置 status|timestamp
     * @return $this
     */
    public function setStatus($status)
    {
        if (!Is::nemstr($status) || !in_array($status, ["waiting","running","success","error"])) return $this;
        $this->status = $status;
        return $this;
    }
    public function setTimestamp($key = "start", $t = 0)
    {
        $this->timestamp[$key] = $t > 0 ? $t : Dater::microtime();
        return $this;
    }

    /**
     * 当前步骤是否 第一步|最后一步|执行中|已结束|已成功|已失败|参数有效
     * @return Bool
     */
    public function isFirstStep() { return $this->idx <= 0;}
    public function isLastStep() { return $this->idx >= count($this->task->step) - 1; }
    public function isRunning() { return $this->status === "running"; }
    public function isSuccess() { return $this->status === "success"; }
    public function isError() { return $this->status === "error"; }
    public function isEnd() { return $this->isSuccess() || $this->isError(); }
    public function isAvailable() { return isset($this->handler) && $this->handler instanceof \Closure; }

    /**
     * 获取上一步 step 实例
     * @return TaskStep
     */
    public function getLastStep()
    {
        if ($this->isFirstStep()) return null;
        return $this->task->getStep($this->idx - 1);
    }

    /**
     * 获取上一步 return
     * @return Array
     */
    public function getLastReturn()
    {
        $lstep = $this->getLastStep();
        if (empty($lstep)) return [];
        return $lstep->return ?? [];
    }

    /**
     * 向 return 中写入数据，使用 Arr::extend 方式
     * !! 会自动添加 arguments["result"] 中的数据
     * @param Array|Bool $args[] 与 Arr::extend 方法参数一致
     * @return $this
     */
    public function extendReturn(...$args)
    {
        //可能存在的上一步 return
        $lrtn = $this->arguments["result"] ?? [];
        if (!is_array($lrtn)) $lrtn = [];
        $ortn = $this->return ?? [];
        if (!is_array($ortn)) $ortn = [];
        $this->return = Arr::extend($lrtn, $ortn, ...$args);
        return $this;
    }
    
    /**
     * __call 调用当前步骤 动态添加的 handler|pending|rollback 闭包方法
     */
    public function __call($method, $args)
    {
        if (!isset($this->$method)) return null;

        //获取 闭包方法
        $closure = $this->$method;
        if ($closure instanceof \Closure) {
            //绑定 当前 step 实例到方法中的 $this
            //方法内部通过 $this->task 获取 Task 实例
            $closure = \Closure::bind($closure, $this, get_class($this));

            //执行 handler 方法之前，将上一步 return 添加到 arguments["result"]
            if ($this->isFirstStep()!==true && $method==="handler") {
                $this->arguments["result"] = Arr::extend(
                    $this->arguments["result"] ?? [],
                    $this->getLastReturn(),
                    //!! 数组使用替换的方式合并，而不是合并去重
                    true
                );
            }

            //执行 closure
            return $closure(...$args);
        }

        return null;
    }
}
    