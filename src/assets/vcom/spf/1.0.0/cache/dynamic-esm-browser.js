import SpfVcomComps from 'https://ms.systech.work/goods/src/vcom/spf/default.js?create=true&mode=mini';
import SpfVcom from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/plugin.js';;
SpfVcomComps.forEach((def, compn) => {Vue.component(compn, def)});
export default SpfVcom;