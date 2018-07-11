<template>
<div></div>
</template>
<script>
    export default {
        mounted() {
            console.log('user_check_begin');
            let dataJson = {
                type : 'accountManage'
            }
            let str = JSON.stringify(dataJson)
            window.postMessage(str, '*');
        },
        methods: {
            userVerify(authKey) {
                let stat = location.search.substr(1);
                window.location.href = '/clients/oauth2/redirect?' + stat + '&stat=' + authKey;
            }
        },
    }
</script>