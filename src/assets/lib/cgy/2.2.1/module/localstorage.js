/**
 * cgy.js 库 convert 扩展
 * 操作 localStorage
 */

const lcs = {};

//cgy 扩展包信息，必须包含
lcs.module = {
    name: 'localstorage',
    version: '0.1.0',
    cgyVersion: '2.0.0'
}

//初始化方法，必须包含
lcs.init = cgy => { cgy.def( {

    /**
     * 操作 localStorage
     */
    localStorage: cgy.proxyer(
        (baseOptions={}) => {
            cgy.convert.current(null);
            cgy.convert.current(cgy.extend({

            }, baseOptions));
            return cgy.localStorage;
        },
        {
            $get: key => window.localStorage.getItem(key),
            $set: (key,val) => window.localStorage.setItem(key, val),
            $del: key => {window.localStorage.removeItem(key)},
            //支持 根据 xpath 从 json 中读取内容
            $getJson: (key, xpath=null, dft=undefined) => {
                let is = cgy.is,
                    sub = is.string(xpath) && xpath !== '',
                    sd = cgy.localStorage.$get(key);
                if (!is.json(sd)) return !sub ? sd : dft;
                sd = JSON.parse(sd);
                if (sub) return cgy.loget(sd, xpath, dft);
                return sd;
            },
            //支持部分覆盖 使用 cgy.extend 方式
            $setJson: (key, val) => {
                let is = cgy.is,
                    osd = cgy.localStorage.$getJson(key);
                if (!is.plainObject(osd)) osd = {};
                if (is.plainObject(val)) {
                    if (!is.empty(osd)) val = cgy.extend(osd, val);
                    let sd = JSON.stringify(val);
                    return cgy.localStorage.$set(key, sd);
                }
                return undefined;
            },
            getFormData(xpath) {
                let key = 'formdata_'+xpath.replace(/\//g, '_'),
                    fd = cgy.localStorage.$get(key);
                if (!fd) return {};
                return JSON.parse(fd);
            },
            setFormData(xpath, data={}) {
                let key = 'formdata_'+xpath.replace(/\//g, '_'),
                    lcs = cgy.localStorage.getFormData(xpath);
                data = (cgy.is.plainObject(data) && !cgy.is.empty(data)) ? data : {};
                if (!cgy.is.empty(lcs)) {
                    data = cgy.extend(lcs, data);
                }
                data = !cgy.is.empty(data) ? JSON.stringify(data) : '{}';
                return cgy.localStorage.$set(key, data);
            },
            removeFormData(xpath) {
                let key = 'formdata_'+xpath.replace(/\//g, '_');
                return cgy.localStorage.$del(key);
            }

        }
    )

} ) }

export default lcs;