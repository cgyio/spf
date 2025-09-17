/**
 * 使用 webusb 接口连接并操作 usb 标签打印机
 * 使用 canvas 生成并打印标签
 * 打印机：佳博 GP-1324D
 * 打印指令：tspl
 */

import cgy from '/cgy/@';
import {encode} from '/cgy/@/modules/gbkcodec.js';

class TagPrinter {
    /**
     * 构造
     * @param {HTMLElement} canvas 元素
     * @param {Object} opt 标签参数，尺寸，间距 等
     * @return TagPrinter 实例
     */
    constructor(canvas, opt={}) {

        //tspl 指令初始化
        //缓存的 tspl 指令数组
        this._tspl_ = [];
        if (cgy.is.defined(opt.tspl)) {
            this.initTspl(opt.tspl);
        }
        
        //初始化 canvas
        if (!cgy.is.elm(canvas)) return false;
        this.canvas = null;
        if (cgy.is.defined(opt.canvas)) {
            this.initCanvas(canvas, opt.canvas);
        }

        //缓存的 printer 实例
        this.printer = null;

    }



    /**
     * usb printer 相关
     */

    /**
     * 使用 webusb 接口连接 usb 打印机，返回连接成功的 printer device 实例
     * @param {Object} filter webusb 接口 requestDevice() 方法使用的 filters 参数，例如 {vendorId: 0x1234}
     * @return {Object} printer device 实例
     */
    async connect(filter={}) {
        if (!cgy.is.defined(navigator) || !cgy.is.defined(navigator.usb)) return null;
        let filters = [];
        if (cgy.is.plainObject(filter) && !cgy.is.empty(filter)) filters.push(filter);
        //连接
        let printer = await navigator.usb.requestDevice({filters}).catch(e=>{return null;});
        if (cgy.is.empty(printer)) {
            throw new Error('无法连接到打印机！');
            return null;
        }
        //初始化
        await printer.open();
        const {configurationValue, interfaces} = printer.configuration;
        await printer.selectConfiguration(configurationValue || 0);
        await printer.claimInterface(interfaces[0].interfaceNumber || 0);
        //缓存
        this.printer = printer;
        window._tag_printer = printer;
        return printer;
    }

    /**
     * 断开 usb 打印机
     * @return {Boolean}
     */
    disconnect() {
        let printer = this.$printer();
        //if (this.isPrinter(printer)) return false;
        try {
            printer.close();
        } catch (e) {
            return false;
        }
        return true;
    }

    //返回传入的 printer 实例 或 this.printer 实例
    $printer(/*printer=null*/) {
        //if (this.isPrinter(printer)) return printer;
        if (!this.ready()) {
            throw new Error('标签打印机还未连接！');
            return null;
        }
        return this.printer;
    }

    /**
     * 获取 usb 打印机信息
     * @return {Object}
     */
    info() {
        let printer = this.$printer();
        let info = {
            opened: false,
            productId: '',
            vendorId: '',
            productName: '',
            seriaNumber: '',
        };
        for (let i in info) {
            if (cgy.is.defined(printer[i])) {
                info[i] = printer[i];
            }
        }
        return info;
    }

    /**
     * 获取 endpointNumber
     * 保存在 printer.configuration.interfaces[0].alternate.endpoints[i].endpointNumber 中
     * @return {Object}
     */
    endPoints() {
        let printer = this.$printer();
        let is = cgy.is,
            rtn = {
                outEndpoint: 1,
                inEndpoint: 2
            },
            conf = printer.configuration,
            intf = conf.interfaces[0] || null,
            altn = is.empty(intf) ? null : intf.alternate,
            endp = is.empty(altn) ? [] : altn.endpoints;
        if (is.empty(endp)) return rtn;
        for (let endpoint of endp) {
            if (!is.defined(endpoint.direction)) continue;
            if (endpoint.direction=='in') rtn.inEndpoint = endpoint.endpointNumber;
            if (endpoint.direction=='out') rtn.outEndpoint = endpoint.endpointNumber;
        }
        return rtn;
    }

    /**
     * 向打印机传输数据 printer.transferOut(endpointNumber, [data queue])
     * @param {Array | UInt8Array} data 要发送到 printer 的指令，指令必须经 Uint8Array 编码
     * @return {USBOutTransferResult}
     */
    async sent(data=[]) {
        let printer = this.$printer();
        const {outEndpoint} = this.endPoints();
        //let raw = cgy.webprint.raw,
        //    sentData = raw ? data : new Uint8Array(data);
        let result = await printer.transferOut(outEndpoint, data);
        console.log(result);
        return result;
    }

    /**
     * 判断 usb 打印机是否已经就绪
     * @return {Boolean}
     */
    ready() {
        return this.isPrinter(this.printer);
    }

    /**
     * 判断 printer 是否 usbdevice 实例
     * @param {Object} printer 打印机实例
     * @return {Boolean}
     */
    isPrinter(printer=null) {
        return cgy.is(printer, 'usbdevice');
    }



    /**
     * tspl 打印指令相关
     */

    /**
     * tspl 默认初始化参数
     */
    tsplInitOption() {
        return {
            width: 100,     //标签宽度 mm
            height: 80,     //标签高度 mm
            gap: 3,         //两张标签之间的间隔 mm
            direction: 1,   //打印方向

            /**
             * 打印 bitmap 数据中 0表示白(不显示)，1表示黑(显示)
             * 如果 reverse=true，则 0表示黑，1表示白
             * 此参数应根据 打印机厂商文档决定
             * 佳博热敏打印机，0表示黑(显示)，1表示白(不显示)
             */
            reverse: true,
        }
    }

    /**
     * tspl 初始化指令
     * @param {Object} opt
     * @param {Boolean} resetOption 是否覆盖原有 option
     * @return {this}
     */
    initTspl(opt={}, resetOption=false) {
        let dft = this.tsplInitOption();
        if (!cgy.is.defined(this.tsplOption) || resetOption) {
            this.tsplOption = cgy.extend({}, dft, opt);
        } else {
            this.tsplOption = cgy.extend({}, dft, this.tsplOption, opt);
        }
        opt = this.tsplOption;

        //如果已存在 tspl 指令数组，则清除
        if (!cgy.is.empty(this._tspl_)) {
            this._tspl_.splice(0);
        }
        
        //写入初始化指令
        this.tspl(`SIZE ${opt.width} mm,${opt.height} mm`);
        this.tspl(`GAP ${opt.gap} mm,0 mm`);
        this.tspl(`DIRECTION ${opt.direction}`);
        this.tspl('CLS');

        return this;
    }

    /**
     * 输入 !!! 一行 !!! tspl 指令
     * 多个参数拼接，如果某个参数是 array ，则以 ...[] 方式合并到 this._tspl_ 指令数组
     * 在此行指令末尾自动添加 \r\n
     * @param {Array} cmds 拼接成一行 tspl 指令的 一个或多个 指令片段
     * @return {this}
     */
    tspl(...cmds) {
        if (cgy.is.empty(cmds)) return this;
        let isarr = a => cgy.is(a,'array,uint8array');
        for (let i=0;i<cmds.length;i++) {
            let cmd = cmds[i];
            if (cgy.is.string(cmd)) {
                cmd = cmd.toUpperCase();
                this._tspl_.push(...encode(cmd));
            } if (isarr(cmd)) {
                console.log(cmd.length);
                for (let i=0;i<cmd.length;i++) {
                    this._tspl_.push(cmd[i]);
                }
                //this._tspl_.push(...cmd);
                //this._tspl_ = this._tspl_.concat(cmd);
            } else {
                continue;
            }
        }
        //自动在指令末尾增加 \r\n
        this._tspl_.push(...encode('\r\n'));
        return this;
    }

    /**
     * 打印
     * 将 this._tspl_ 指令数组中的指令输出到 打印机，开始打印
     * @param {Integer} pages 要打印的张数，默认1
     * @return {USBOutTransferResult}
     */
    async print(pages=1) {
        let printer = this.$printer();
        //增加 print 指令
        this.tspl(`PRINT 1,${pages}`);
        //编码 this._tspl_ 指令
        let cmd = new Uint8Array(this._tspl_);
        //发送指令
        let result = await this.sent(cmd, printer);
        //重新初始化 tspl
        this.initTspl();

        return result;
    }



    /**
     * canvas 标签生成 相关
     */

    /**
     * canvas 默认初始化参数
     */
    canvasInitOption() {
        return {
            /**
             * canvas 模拟视网膜屏效果，scale 个像素模拟 1 个像素，解决文字模糊问题
             * !!! scale 必须是 某个正整数的平方，如：1,4,9,16,... 这样才能等比例缩放
             */
            scale: 1,
                
            //绘制参数，字体，字号，行高 等
            draw: {

                //可选的字体样式类型
                ftype: {
                    //默认字体
                    default: {
                        tpn: '默认文字',
                        fsize: 24,
                        font: 'Microsoft Yahei',
                        line: 1.2,
                        style: '',
                    },
                    //标题文字
                    title: {
                        tpn: '产品名称文字',
                        fsize: 48,
                        font: 'Microsoft Yahei',
                        line: 1.2,
                        style: '900',
                    },
                    //突出显示文字
                    notice: {
                        tpn: '突出显示文字',
                        fsize: 36,
                        font: '楷体',
                        line: 1.2,
                        style: '',
                    },
                    //次要字体
                    second: {
                        tpn: '次要文字',
                        fsize: 20,
                        font: 'Microsoft Yahei',
                        line: 1.2,
                        style: '',
                    },
                },

                //间距
                gap: {
                    label: 2,   //label 右侧间距 mm
                    block: 1,   //段后 mm
                },

            },
        }
    }

    /**
     * 初始化 canvas 
     * @param {HTMLElement} canvas
     * @param {Object} opt
     * @param {Boolean} resetOption 是否覆盖原有 option
     * @return {this}
     */
    initCanvas(canvas, opt={}, resetOption=false) {
        if (!cgy.is.elm(canvas)) {
            throw new Error('未指定 canvas 元素');
            return this;
        }
        this.canvas = canvas;

        let tag = this.tsplOption,  //需要使用 tag 标签参数
            dft = this.canvasInitOption();
        //从
        if (!cgy.is.defined(this.canvasOption) || resetOption) {
            this.canvasOption = cgy.extend({}, dft, opt);
        } else {
            this.canvasOption = cgy.extend({}, dft, this.canvasOption, opt);
        }
        opt = this.canvasOption;
        let //canvas = this.$canvas(),
            tw = tag.width,     //标签宽度 mm
            th = tag.height,    //标签高度 mm
            tg = tag.gap,      //标签边距 mm
            //canvas 尺寸 由 mm 宽度转换为 dot
            m2d = mm => this.mm2dot(mm),
            cw = m2d(tw),
            ch = m2d(th),
            gap = m2d(tg),
            sci = Math.sqrt(opt.scale);
        
        /**
         * tspl 打印 bitmap 指令需要将 8 个像素的黑白值(0/1) 合并为 1 个 0~255 的数值
         * 因此需要 canvas 宽度 必须可以被 8 整除
         */
        if (cw%8!=0) cw += (8 - cw%8);

        /**
         * 将 canvas 先放大，在由 canvas.ctx.scale() 方法 显示正常大小
         * 解决 高清屏下 文字模糊的问题
         * 本质上是在 模拟视网膜屏的显示原理，多个像素 合并为 一个像素
         */
        canvas.width = cw * sci;
        canvas.height = ch * sci;
        //css 使用原尺寸
        canvas.style.width = `${cw}px`;
        canvas.style.height = `${ch}px`;
        //使用 ctx 的 scale 方法，显示成正常尺寸（实际像素已放大 scale 倍）
        let ctx = this.$ctx();
        if (sci>1) ctx.scale(sci, sci);

        //缓存处理后的 尺寸参数
        this.canvasSize = {
            //显示的尺寸
            show: {
                width: cw,
                height: ch,
                //边距
                gap,
            },
            //实际尺寸（经过 scale 放大的）
            real: {
                width: canvas.width,
                height: canvas.height,
                //边距
                gap: gap * sci,
            },
        }

        //开始初始化 canvas 内容，清空画布
        this.clearCanvas();

        return this;
    }

    /**
     * 清空 canvas 内容
     * 使用白色填充画布
     * @return canvas context
     */
    clearCanvas() {
        let ctx = this.$ctx(),
            sz = this.canvasSize.show,
            cw = sz.width,
            ch = sz.height;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0,0,cw,ch);
        return ctx;
    }

    //获取 canvas 实例
    $canvas() {
        if (!cgy.is.elm(this.canvas)) {
            throw new Error('标签画布还未指定！');
            return null;
        }
        return this.canvas;
    }

    //canvas.getContext('2d')
    $ctx() {
        return this.$canvas().getContext('2d');
    }

    /**
     * 打印 canvas 内容
     * 先将 canvas 内容转换为 bitmap 黑白图
     * 再使用 tspl bitmap 指令，打印 bitmap
     * @param {Integer} pages 打印张数
     * @return {USBOutTransferResult}
     */
    async printCanvas(pages=1) {
        let bitmap = await this.canvasToBitmap(),
            csz = this.canvasSize,
            x = 0,
            y = 0,
            width = csz.show.width/8,   //因为 8 个像素的 0/1 值合并为 1 个 0~255 数值，因此打印宽度需要 /8
            height = csz.show.height,   //黑白图，打印高度 == 高度 dot 数
            mode = 0;                   //0=覆盖模式
        //console.log(x,y,width,height,bitmap);
        //console.log(bitmap.length);
        //写入指令
        this.tspl(
            `BITMAP ${x},${y},${width},${height},${mode},`,
            bitmap
        );
        //console.log(this._tspl_.length);
        //发送指令
        let result = await this.print(pages);
        return result;
    }

    /**
     * canvas 内容转为 热敏标签打印机的 bitmap 
     * imageData 格式：一维数组，每个像素由 4 个 0~255 的值表示 RGBA
     * 需要转换为 黑白图片，每个像素由 0/1 表示 黑/白，
     * bitmap 格式：一维数组，每 8 个像素的 0/1 值合并为 1 个 0~255 数值
     */
    async canvasToBitmap() {
        await cgy.wait(100);
        let canvas = this.$canvas(),
            ctx = this.$ctx(),
            imageData = ctx.getImageData(0,0, canvas.width, canvas.height),
            csz = this.canvasSize,
            cw = csz.real.width,
            ch = csz.real.height,
            iw = imageData.width,
            ih = imageData.height,
            id = imageData.data,
            reverse = this.tsplOption.reverse,
            gray = 128,
            sci = this.canvasOption.scale,
            temp = [],
            bm = [];
        //console.log(cw,ch,iw,ih);
        //console.log('imagedata length:', id.length, id);

        //let tpln = 0,
        //    ix = 0;
        
        if (sci>1) {
            //如果 canvasOption.scale>1 存在放大，此处要将 scale 个像素 合并为 1 个像素
            let n = Math.sqrt(sci);     //scale 必须是正整数的平方，如 1,4,9,16
            //console.log(sci,n,'hd!!!!');
            for (let y=0;y<ih;y+=n) {
                //if (y>0) break;
                for (let x=0;x<iw*4;x+=(4*n)) {
                    //console.log(ix);
                    let rows = [];
                    for (let i=0;i<n;i++) {
                        let idx = (y+i)*iw*4+x;
                        rows.push(id.slice(idx, idx+(4*n)));
                    }
                    //console.log(rows);
                    if (rows.length!=n && rows[0].length!=4*n) {
                        console.log('error row length', rows.length, n, rows[0].length, n*4);
                    }
                    //console.log(rows[0].length, rows[1].length);
                    let nrgba = this.rgbaBinning(...rows);
                    //console.log(nrgba);
                    if (!nrgba.length || nrgba.length<4) {
                        console.log('error nrgba length', nrgba.length, 4);
                    }
                    //console.log(temp.length, nrgba.length);
                    temp.push(...nrgba);
                    //tpln += nrgba.length;
                    //ix ++;
                }
            }
            iw = iw/n;
            ih = ih/n;
        } else {
            temp = [...id];
        }
        //console.log(iw,ih);
        //console.log('111', temp.length, temp);
        bm = [];
        //return false;

        //rgba --> 黑白二值化
        for (let i=0;i<temp.length;i+=4) {
            let ti = this.rgbaBitting(temp[i],temp[i+1],temp[i+2],temp[i+3]);
            bm.push(ti);
        }
        //console.log('222', bm.length, bm);
        temp = [];

        //将 8 个像素的 0/1 值合并为 1 个 0~255 数值
        //console.log(iw,ih);
        for (let y=0;y<ih;y++) {
            for (let x=0;x<iw;x+=8) {
                let idx = y*iw+x,
                    nbi = parseInt(bm.slice(idx,idx+8).join(''),2);
                temp.push(nbi);
            }
        }
        bm = [];

        //Uint8Array 编码
        bm = new Uint8Array(temp);
        //console.log('bitmap length:', bm.length, bm);

        return bm;
    }

    /**
     * 多个像素合并为 1 个像素
     * 算法：rgba 计算平均值
     * @param {Array} rows 4个像素合并 则 [ [r1,g1,b1,a1, r2,g2,b2,a2], [r3,g3,b3,a3, r4,g4,b4,a4] ]，以此类推
     * @return {Array} 计算得到的 rgba 值数组
     */
    rgbaBinning(...rows) {
        let r = 0,
            g = 0,
            b = 0,
            a = 0,
            n = Math.sqrt(this.canvasOption.scale),    //rows.length,                    // n*n == canvasOption.scale
            avg = i => Math.round(i/(n*n));     //合并算法：计算 rgba 平均值
        for (let y=0;y<n;y++) {
            for (let x=0;x<n*4;x+=4) {
                r += rows[y][x];
                g += rows[y][x+1];
                b += rows[y][x+2];
                a += rows[y][x+3];
            }
        }
        r = avg(r);
        g = avg(g);
        b = avg(b);
        a = avg(a);
        return [r,g,b,a];
    }

    /**
     * 根据某个像素的 rgba 4 个 0~255 数值 和 tsplOption.reverse 计算黑白图中此像素的 0/1 值
     */
    rgbaBitting(r,g,b,a) {
        let reverse = this.tsplOption.reverse,
            gray = 255 * 0.66;  //128，灰度值超过此值显示为白(不显示)，否则黑(显示)
        
        if (a<=0) {
            //透明色显示为白(不显示)
            return reverse ? 1 : 0;
        }

        let gi = (r+g+b)/3;
        if (gi>gray) {
            //灰度值 > gray 显示为白(不显示)
            return reverse ? 1 : 0;
        } else {
            return reverse ? 0 : 1;
        }
    }



    /**
     * canvas 标签绘制操作
     */

    /**
     * 处理 通用文本绘制参数
     * @param {Object} opt 文字绘制参数
     * @param {Boolean} fix 是否自动转换其他单位数值到 dot 默认 true
     * !! fsize / gap / maxw 单位 dot 
     * !! line 单位 %
     */
    fixTextDrawOption(opt={}, fix=true) {
        let is = cgy.is,
            ext = cgy.extend,
            isd = is.defined,
            csz = this.canvasSize.show,
            copt = this.canvasOption.draw,
            dft = copt.ftype.default;
        //如果指定的使用某个 ftype
        if (isd(opt.ftype)) {
            let ftp = opt.ftype;
            if (isd(copt.ftype[ftp])) {
                opt = ext({}, copt.ftype[ftp], opt);
            }
            Reflect.deleteProperty(opt, 'ftype');
        }
        //如果 fsize/font/line/.. 等参数引用了某个 ftype
        let ks = Object.keys(copt.ftype.default);
        for (let ki of ks) {
            if (!isd(opt[ki]) || !isd(copt.ftype[opt[ki]])) continue;
            let ftp = opt[ki];
            opt[ki] = copt.ftype[ftp][ki];
        }
        //通用文本参数必须从 ftype.default extend
        opt = ext({}, dft, opt);
        //增加一些不常用的 参数
        opt = ext({
            maxw: 0,            //指定文本最大占用宽度 dot 默认不指定
            gap: 0,             //默认文本的段后 dot 默认不指定
            color: '#000000',   //默认字体颜色
            style: '',          //默认其他字体样式
            align: 'left',      //默认文字对齐方式
            baseline: 'top',    //ctx.textBaseline
            position: 'relative',   //布局位置形式，relative=相对位置，absolute=绝对位置
            wrap: false,        //是否自动换行
        }, opt);

        if (fix) {
            //单位转换 将所有 % 表示的数值，转为 dot
            let fsz = opt.fsize;
            if (isd(opt.line) && opt.line<=2) opt.line = Math.round(fsz * opt.line);
            if (isd(opt.maxw) && opt.maxw>0 && opt.maxw<1) opt.maxw = csz.width * opt.maxw;
            if (isd(opt.gap) && opt.gap>0 && opt.gap<8) opt.gap = this.mm2dot(opt.gap);
        }
        
        //console.log(opt);
        return opt;
    }

    /**
     * canvas 绘制文本操作
     * @param {String} text
     * @param {Object} opt 绘制参数
     * @return {Object} 文本所占区域右下角坐标 {x:.., y:..}
     */
    //根据通用文本绘制参数，绘制文本
    canvasDrawText(text, opt={}) {
        let is = cgy.is,
            ext = cgy.extend,
            csz = this.canvasSize.show,
            ctx = this.$ctx(),
            copt = this.canvasOption.draw;
        //为必须参数指定默认值
        opt = ext({
            x: csz.gap, //默认绘制起始 x
            y: csz.gap, //默认绘制起始 y
        }, opt);
        //如果 text 要绘制的文本为空，直接返回起始坐标
        if (!is.string(text) || text=='') return {x: opt.x, y: opt.y};
        //处理通用绘制参数
        opt = this.fixTextDrawOption(opt);
        //console.log(opt);

        //绘制
        if (opt.wrap && opt.wrap==true) {
            //自动换行
            opt.wrap = false;
            if (opt.maxw && opt.maxw<1) {
                opt.maxw = (csz.width - csz.gap * 2) * opt.maxw;
            } else {
                opt.maxw = csz.width - csz.gap * 2;
            }
            return this.canvasDrawWrapText(text, ext({
                nx: csz.gap
            }, opt));
        } else {
            //普通绘制
            //处理通用绘制参数
            opt = this.fixTextDrawOption(opt);
            //console.log(opt);
            ctx.textBaseline = opt.baseline || 'top';
            ctx.textAlign = opt.align || 'left';
            ctx.fillStyle = opt.color;
            ctx.font = `${opt.style=='' ? '' : opt.style+' '}${opt.fsize}px ${opt.font}`;
            if (opt.maxw<=0) {
                ctx.fillText(text, opt.x,opt.y);
            } else {
                ctx.fillText(text, opt.x,opt.y, opt.maxw);
            }
            return {
                x: opt.x + (opt.maxw<=0 ? opt.fsize*text.length : opt.maxw),
                y: opt.y + opt.line + opt.gap
            }
        }
    }
    //绘制指定宽度的文字，指定宽 n 个字，用于标签项目名称 label
    canvasDrawLabel(text, opt={}) {
        let is = cgy.is,
            ext = cgy.extend,
            reg = cgy.reg,
            csz = this.canvasSize.show,
            copt = this.canvasOption.draw;
        //为必须参数指定默认值
        opt = ext({
            x: csz.gap,     //默认绘制起始 x
            y: csz.gap,     //默认绘制起始 y
            w: 4,           //指定宽度 w 个字
            gap: copt.gap.label,    //label 文本右侧间距，默认 2mm
            style: 'bold',          //label 默认加粗
        }, opt);
        //text 必须是中文，且不为空
        if (!is.string(text) || text=='' || !reg('cn').test(text) || text.length<=1) return {x:opt.x, y:opt.y};
        //如果给定字符串长度 超过 指定宽度，则将字符串长度 设为 指定宽度
        if (text.length>opt.w) opt.w = text.length;
        //处理通用绘制参数
        opt = this.fixTextDrawOption(opt);
        
        //开始绘制
        let fsz = opt.fsize,
            gap = opt.gap,
            fw = opt.w * (fsz * 1),  //加粗字体太紧密
            fx = (fw - fsz)/(text.length - 1),
            larr = text.split(''),
            tx = opt.x,
            ty = opt.y;
        for (let i=0;i<larr.length;i++) {
            let li = larr[i],
                opti = ext({}, opt, {x:tx, y:ty, gap:0});
            //绘制
            this.canvasDrawText(li, opti);
            //最后一个字
            if (i==larr.length-1) {
                tx += fsz;
            } else {
                tx += fx;
            }
        }
        return {
            x: opt.x + fw + gap,
            y: ty + opt.line
        };
    }
    //绘制自动换行文本，如 配料表
    canvasDrawWrapText(text, opt={}) {
        let is = cgy.is,
            ext = cgy.extend,
            reg = cgy.reg,
            csz = this.canvasSize.show,
            copt = this.canvasOption.draw;
        //为必须参数指定默认值
        opt = ext({
            x: csz.gap,     //默认绘制起始 x
            y: csz.gap,     //默认绘制起始 y
            nx: csz.gap,    //自动换行后，新一行的起始 x
            maxw: csz.width - csz.gap * 2,    //行最大宽度
        }, opt);
        //处理通用绘制参数
        opt = this.fixTextDrawOption(opt);
        //console.log(opt);
        //分字
        let tarr = text.split(''),
            tx = opt.x,
            ty = opt.y,
            fsz = opt.fsize,
            gap = opt.gap;
        //逐字绘制
        for (let i=0;i<tarr.length;i++) {
            let ti = tarr[i];
            //绘制
            this.canvasDrawText(ti, ext({}, opt, {x:tx, y:ty, maxw:0, gap:0}));
            //如果已是最后一个字，直接退出
            if (i==tarr.length-1) break;
            //判断是否换行
            let iszh = reg('cn').test(ti),            //判断是否中文
                niszh = reg('cn').test(tarr[i+1]);    //判断下一个字是否中文
            if (iszh) {
                tx += fsz;
            } else {
                tx += (fsz * 0.5);
            }
            let ntx = fsz;
            if (!niszh) ntx = fsz * 0.5;
            tx = Math.round(tx);
            if (tx - opt.nx>=opt.maxw - ntx && !'，|。|,|.|（|）|(|)|；|;'.split('|').includes(tarr[i+1])) {
                //剩余空间已不够显示下一个字，则换行
                //同时如果下一个字是标点，则不换行
                tx = opt.nx;
                ty += opt.line;
            }
        }
        return {
            x: tx,
            y: ty + opt.line + gap
        };
    }
    //绘制单行标签项目，指定此行文字占据最大宽度
    canvasDrawSingleRowItem(label, value, opt={}) {
        let is = cgy.is,
            ext = cgy.extend,
            csz = this.canvasSize.show,
            copt = this.canvasOption.draw;
        //为必须参数指定默认值
        opt = ext({
            x: csz.gap,     //起始 x 坐标 默认从标签边距内开始
            y: csz.gap,     //起始 y 坐标 默认从标签边距内开始
            //w: 0.6,         //指定本行文本占据的最大宽度 %
            //通用参数
            label: {},
            value: {},
        }, opt);

        if (!is.string(label) || !is.string(value) || label=='' || value=='') {
            return {x:opt.x, y:opt.y};
        }

        let tx = opt.x,
            ty = opt.y;
        //绘制 label
        if (is.plainObject(opt.label)) {
            let lo = this.fixTextDrawOption(opt.label),
                vo = this.fixTextDrawOption(opt.value),
                lb = this.canvasDrawLabel(label, ext({}, opt.label, {
                    x: opt.x, 
                    y: vo.fsize>lo.fsize ? opt.y + (vo.fsize-lo.fsize)/2 : opt.y
                }));
            tx = lb.x;
            ty = lb.y;
        }
        //绘制 value 文本
        let mw = opt.value.maxw ? (opt.value.maxw<=0 ? 0.6 : opt.value.maxw) : 0.6, //指定本行文本占据的最大宽度 %
            maxw = (csz.width - csz.gap * 2) * mw - tx,
            dt = this.canvasDrawText(value, ext({}, opt.value, {x:tx, y:opt.y, maxw}));
        return {
            x: opt.x + csz.width * opt.w,
            y: dt.y
        };
    }
    //绘制多行标签项目，根据标签项内容自动换行 指定此行文字占据最大宽度
    canvasDrawMultiRowItem(label, value, opt={}) {
        let is = cgy.is,
            ext = cgy.extend,
            csz = this.canvasSize.show,
            copt = this.canvasOption.draw;
        //为必须参数指定默认值
        opt = ext({
            x: csz.gap,     //起始 x 坐标 默认从标签边距内开始
            y: csz.gap,     //起始 y 坐标 默认从标签边距内开始
            nx: csz.gap,    //自动换行后的起始 x 坐标 默认满标签显示，=0时，由 label 决定起始 x
            w: 1,           //指定本行文本占据的最大宽度 %
            gap: copt.gap.block,    //此项目段后距离 默认 1mm
            //通用参数
            label: {},
            value: {},
        }, opt);

        if (!is.string(label) || !is.string(value) || label=='' || value=='') {
            return {x:opt.x, y:opt.y};
        }

        let tx = opt.x,
            ty = opt.y,
            gap = opt.gap>0 ? this.mm2dot(opt.gap) : 0;
        //绘制 label
        if (is.plainObject(opt.label)) {
            let lb = this.canvasDrawLabel(label, ext({}, opt.label, {x:opt.x, y:opt.y}));
            tx = lb.x;
            ty = lb.y;
        }
        //绘制 value 文本
        let dt = this.canvasDrawWrapText(value, ext({}, opt.value, {
                x: tx, 
                y: opt.y, 
                nx: opt.nx<=0 ? tx : opt.nx,
                maxw: (csz.width - csz.gap * 2) * opt.w
            }));
        //console.log(dt);
        return {
            x: csz.width * opt.w,
            y: dt.y + gap
        };
    }
    
    /**
     * canvas 绘制 logo
     * !! logo 图片必须为 正方形
     */
    canvasDrawLogo(src, opt={}) {
        if (!cgy.is.string(src) || src=='') return false;
        let is= cgy.is,
            ext = cgy.extend,
            cav = this.$canvas(),
            ctx = this.$ctx(),
            csz = this.canvasSize.show,
            copt = this.canvasOption.draw,
            img = new Image();
        //console.log(src);
        img.src = src;
        //console.log(opt);
        opt = ext({
            //默认不指定 起始坐标
            x: 0,
            y: 0,
            //默认 logo 图片为标签总宽度的 20%
            width: 0.2,
        }, opt);
        //console.log(opt);
        let cw = csz.width - 2 * csz.gap,
            iw = cw * opt.width,
            ih = cw * opt.width,
            ix = opt.x>0 ? opt.x : csz.width - iw - csz.gap,
            iy = opt.y>0 ? opt.y : csz.height - ih - csz.gap;
        img.onload = function() {
            ctx.drawImage(this, ix,iy, iw,ih);
        }
    }



    /**
     * 单位转换
     */

    /**
     * mm 转为 热敏标签打印机 dot 点数
     * 打印机分辨率 = 203dpi dot/英寸
     * 1英寸 = 25.4mm
     * 因此 1mm = 203/25.4 ≈ 8 dot
     */
    mm2dot(mm=1) {
        //dot 为整数
        const dpi = 203;
        const inch = 25.4;
        const dpm = Math.round(dpi/inch);   //8
        return mm * dpm;
    }

    /**
     * 字号 转换为 px / dot 点数
     * 1mm = 3.78px ≈ 8dot
     * 1px = 8/3.78 ≈ 2.1164dot
     * 1pt = 1.34px ≈ 2.8360dot 磅
     */
    mm2px(mm=1) {
        //px 可以为小数
        return mm * 3.78;
    }
    px2dot(px=1) {
        //dot 为整数
        return Math.round(px * 2.1164);
    }
    fsz2px(fsz='小五') {
        //字号 --> px
        const fszl = {
            '八号':  5,
            '七号':  5.5,
            '小六':  6.5,
            '六号':  7.5,
            '小五':  9,
            '五号':  10.5,
            '小四':  12,
            '四号':  14,
            '小三':  15,
            '三号':  16,
            '小二':  18,
            '二号':  22,
            '小一':  24,
            '一号':  26,
            '小初':  36,
            '初号':  42,
        };
        if (cgy.is.defined(fszl[fsz])) return fszl[fsz];
        return 0;
    }
    fsz2dot(fsz='小五') {
        let px = this.fsz2px(fsz);
        return this.px2dot(px);
    }
    pt2px(pt=1) {
        //px 可以为小数
        return pt * 1.34;
    }
    pt2dot(pt=1) {
        return this.px2dot(this.pt2px(pt));
    }

}

const tagprint = {}

//cgy 扩展包信息，必须包含
tagprint.module = {
    name: 'tagprint',
    version: '0.1.1',
    cgyVersion: '2.0.0'
}

//初始化方法，必须包含
tagprint.init = cgy => { 

    cgy.def( {
        //创建 TagPrinter 标签打印机实例
        tagprint: (...args) => new TagPrinter(...args),
    } ); 
}

//export TagPrinter;
export default tagprint;