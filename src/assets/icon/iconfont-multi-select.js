/**
 * 在 iconfont 图标展示页面 快速选择图标到 购物车
 *  0   F12 
 *  1   copy 这些代码到控制台
 *  2   执行 sel(开始图标序号, 结束图标序号) 
 * 
 * !! 购物车一次最多放置 2000 个图标
 * !! 单次 sel 不要超过 1000 个图标，会很慢
 */

let gets = () => {
    let bs = document.querySelectorAll(".icon-gouwuche1");
    return Array.from(bs);
}
let btns = gets();
let wait = (t=100) => new Promise((resolve,reject) => {
    setTimeout(() => {
        return resolve();
    }, t);
});
let clk = async (s,e,w=100) => {
    btns.slice(s,e).forEach(async i => {
        await wait(w);
        i.click();
    });
    await wait(w);
}
let sel = async (s,e,w=100) => {
    await clk(s,e,w);
    console.log(`item ${s} ~ ${e} selected!`);
}