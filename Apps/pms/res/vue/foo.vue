<template>
<a href="javascript:alert('foobar')" class="foo">{{foo}}</a>
</template>

<script>
module.exports = {
    data: function(){
        return {
            foo: 'foobar'
        };
    }
}
</script>

<style>
a.foo {color: red;}
</style>