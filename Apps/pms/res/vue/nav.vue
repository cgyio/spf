<template>
    <div class="-dv-pms-navbox">
        <div 
            :class="'-dv-pms-navbtn'+(isActive ? '_active' : (isOpen ? '_open' : ''))" 
            @click="$emit('btn-click',idx)"
        >
            <i 
                class="iconfont" 
                :class="'icon-'+icon"
            ></i>
            <div>{{text}}</div>
            <i 
                :class="['iconfont', 'icon-'+(isOpen ? 'fold' : 'unfold')]" 
                style="font-size:1.4rem;"
            ></i>
        </div>
        <div 
            v-if="isOpen" 
            :class="'-dv-pms-navsub'+(isActive ? '_active' : '')" 
        >
            <div 
                v-for="(item,index) in subnavs" 
                :class="'-dv-pms-navitem'+(isActive && actsubidx==index ? '_active' : '')" 
                :key="id+'_'+item.id" 
                @click="$emit('sub-click',[idx,index])"
            >
                <i></i>
                <div>{{item.text}}</div>
            </div>
        </div>
    </div>
</template>

<script>
module.exports = {
    props: {
        id: String,
        idx: Number,
        icon: String,
        text: String,
        isOpen: Boolean,
        isActive: Boolean,
        subnavs: Array,
        actsubidx: Number
    },
    methods: {

    }
}
</script>

<style>
[class^="-dv-pms-navbody"] {display: block;}
    [class^="-dv-pms-navbody"] > .-dv-pms-navbox {display: block;}
        [class^="-dv-pms-navbtn"] {
            display: flex; width: 100%; height: 4.2rem;
            align-items: center;
            cursor: pointer;
        }
            [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"][class*="_open"] {background-color: var(--c-black); color: var(--c-white);}
            [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"][class*="_active"] {background-color: var(--c-black); color: var(--c-cyan);}
            [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"]:hover {background-color: var(--c-grayblue); color: var(--c-white);}
            [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"][class*="_active"]:hover {background-color: var(--c-black);}
            [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"][class*="_open"] {background-color: var(--c-gray-2); color: var(--c-black);}
            [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"][class*="_active"] {background-color: var(--c-warnyellow); color: var(--c-black);}
            [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"]:hover {background-color: var(--c-gray-3); color: var(--c-black);}
            [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navbtn"][class*="_active"]:hover {background-color: var(--c-warnyellow);}
            [class^="-dv-pms-navbtn"] > i {
                width: 4.2rem; height: 4.2rem; 
                text-align: center; font-size: 2rem; line-height: 4.2rem;
            }
            [class^="-dv-pms-navbtn"] > div {
                flex: 1; height: 4.2rem; 
                line-height: 4.2rem; font-size: 1.6rem;
            }
        [class^="-dv-pms-navsub"] {
            display: block; padding-bottom: 1rem;
        }
            [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] {background-color: var(--c-darkergrayblue);}
            [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"][class*="_active"] {color: var(--c-white);}
            [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] {background-color: var(--c-gray-1);}
            [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"][class*="_active"] {background-color: var(--c-warnyellow); color: var(--c-black);}
                [class^="-dv-pms-navitem"]{
                    display: flex; width: 100%; height: 3.6rem;
                    align-items: center;
                    cursor: pointer;
                }
                    [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] > [class^="-dv-pms-navitem"][class*="_active"] {background-color: var(--c-yellow); color: var(--c-black);}
                    [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] > [class^="-dv-pms-navitem"]:hover {background-color: var(--c-cyan); color: var(--c-black);}
                    [class^="-dv-pms-navbody"][class*="_dark"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] > [class^="-dv-pms-navitem"][class*="_active"]:hover {background-color: var(--c-yellow);}
                    [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] > [class^="-dv-pms-navitem"][class*="_active"] {background-color: var(--c-cyan); color: var(--c-black);}
                    [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] > [class^="-dv-pms-navitem"]:hover {background-color: var(--c-white);}
                    [class^="-dv-pms-navbody"][class*="_light"] > .-dv-pms-navbox > [class^="-dv-pms-navsub"] > [class^="-dv-pms-navitem"][class*="_active"]:hover {background-color: var(--c-cyan);}
                    [class^="-dv-pms-navitem"] > i {
                        width: 4.2rem; height: 3.6rem; 
                    }
                    [class^="-dv-pms-navitem"] > div {
                        flex: 1; height: 3.6rem; 
                        line-height: 3.6rem;
                    }
</style>