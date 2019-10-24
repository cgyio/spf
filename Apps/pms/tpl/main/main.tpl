<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>乾震PMS系统</title>
%{css::/css/normalize/8.0.1.css,/css/iconfont/taobao/iconfont.css,/css/du-var.css,/css/du-base.css,/css/pms/css/main.css}%
<style>
body {
    background-color: var(--c-gray-2);
}
</style>
</head>
<body>
<div id="PMS_navbar" class="-du-navbar_dark-scroll_yellow_dark10">
    <div id="PMS_logo"></div>
    <div id="PMS_nav" class="-dv-pms-navbody_dark">
        <pms-nav
            v-for="(nav,index) in navs" 
            v-bind="nav" 
            :idx="index" 
            :is-active="actidx==index" 
            :is-open="openidx==index || actidx==index" 
            :actsubidx="actsubidx" 
            :key="'PMS_nav_'+nav.id" 
            @btn-click="toggleNav" 
            @sub-click="activeSubnav" 
        ></pms-nav>
    </div>
</div>
<div id="PMS_main"></div>
<div id="PMS_tablebox">
    <div id="PMS_table_topbar">
        <a href="" class="pms-table-topbarbtn"><i class="iconfont icon-back"></i></a>
        <div id="PMS_table_tabs">
            <select>
                <option 
                    v-for="(tab, index) in tabs" 
                    v-bind:key="tab.tbn" 
                    v-bind:value="index" 
                >{{ tab.text }}</option>
            </select>
        </div>
        <a href="" class="pms-table-topbarbtn"><i class="iconfont icon-cascades"></i></a>
        <a href="" class="pms-table-topbarbtn"><i class="iconfont icon-more"></i></a>
    </div>
    <div id="PMS_table" class="pms-table">

    </div>
</div>
<script type="text/x-template" for="-dj-template-foo-bar">
    <div id="foobar">${foo/bar}</div>
</script>
<!--
    <div v-for="(nav,index) in navs" class="-du-navbox">
            <pms-navbtn 
                :id="nav.id" 
                :idx="index" 
                :icon="nav.icon" 
                :text="nav.text" 
                :is-active="actidx == index" 
                :is-open="nav.isOpen || actidx == index" 
                @btn-click="toggleNav" 
            ></pms-navbtn>
            <pms-navsub
                :id="nav.id" 
                :idx="index"  
                :is-active="actidx == index" 
                :is-open="nav.isOpen || actidx == index" 
                :items="nav.subnavs" 
                :actsubidx="actsubidx" 
                @sub-click="activeSubnav" 
            ></pms-navsub>
        </div>
-->
<!--<div id="PMS_topbar" class="-du-topbar-bgc_white">
    <img class="logo" src="/res/app/pms/icon/qz_logo.svg">
    <span class="gap"></span>
    <span class="title">
        <span class="content">{{ pageTitle }}</span>
        <a href="" class="btn -bgc_cyan"><i class="iconfont icon-moreandroid"></i></a>
    </span>
    <span class="gap"></span>
    <a href="" class="btn"><i class="iconfont icon-moreandroid"></i></a>
    <span class="gap"></span>
    <div class="container content">
        topbar
    </div>
</div>-->
%{js::/js/jquery/3.4.1.js,/js/vue/2.6.10.dev.js,/js/vue/http-vue-loader.js,/js/dj-base.js}%
<script>
$(function(){
    //DommyJS init
    DommyJS.initialize({
        pageid : 'pms',
        /*vue : {
            components : {
                "pms-nav" : "pms/nav"
            }
        },*/
        pms : {
            enabled : true,
            path : 'pms/pms'
        },
    },function(){
        //缓存数据
        dj.cache({
            navs : [
                    {
                        id: 'stocker',
                        icon: 'cart',
                        text: '货品及库存管理',
                        //isOpen: false,
                        subnavs : [
                            {
                                id: 'storage',
                                text: '仓库及库位管理',
                                src: ''
                            },
                            {
                                id: 'goods',
                                text: '货品管理',
                                src: ''
                            },
                            {
                                id: 'inout',
                                text: '库存及出入库管理',
                                src: ''
                            },
                            {
                                id: 'deliver',
                                text: '发货管理',
                                src: ''
                            },
                            {
                                id: 'count',
                                text: '统计与报表',
                                src: ''
                            }
                        ]
                    },
                    {
                        id: 'pm',
                        icon: 'shop',
                        text: '生产管理',
                        //isOpen: false,
                        subnavs : [
                            {
                                id: 'pds',
                                text: '品种及配方管理',
                                src: ''
                            },
                            {
                                id: 'plan',
                                text: '生产计划与排产',
                                src: ''
                            },
                            {
                                id: 'prepare',
                                text: '备料及申购操作',
                                src: ''
                            },
                            {
                                id: 'batchrecord',
                                text: '批生产记录管理',
                                src: ''
                            },
                            {
                                id: 'count',
                                text: '统计与报表',
                                src: ''
                            }
                        ]
                    },
                    {
                        id: 'finance',
                        icon: 'sponsor',
                        text: '财务管理',
                        //isOpen: false,
                        subnavs : [
                            {
                                id: 'price',
                                text: '品种价格管理',
                                src: ''
                            },
                            {
                                id: 'produce',
                                text: '投产财务统计',
                                src: ''
                            },
                            {
                                id: 'count',
                                text: '统计与报表',
                                src: ''
                            }
                        ]
                    }
                ],
        });
        //vue object
        $vue.create('PMS_nav',{
            data: {
                navs: dj.cache('navs'),
                openidx: 0,
                actidx: 0,
                actsubidx: 0
            },
            components: $vue.import({
                "pms-nav": "pms/nav"
            }),
            computed: {
                
            },
            methods: {
                toggleNav: function(navidx){
                    if(this.actidx!=navidx){
                        if(this.openidx!=navidx) this.openidx = navidx;
                    }
                },
                activeSubnav: function(actidx){
                    this.actidx = actidx[0];
                    this.actsubidx = actidx[1];
                }
            }
        });
    });
    
});
</script>
</body>
</html>