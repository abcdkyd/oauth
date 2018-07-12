<template>
<div>1</div>
</template>
<script>
    export default {
        mounted() {

        },
        methods: {
            postMessage() {
                setTimeout(function() {
                    let dataJson = {
                        type : 'accountManage'
                    }
                    let str = JSON.stringify(dataJson)
                    window.postMessage(str, '*');
                }, 600);
            },
            userVerify(authKey) {
                let stat = location.search.substr(1);
                window.location.href = '/clients/oauth2/redirect?' + stat + '&stat=' + authKey;
            },
        },
    }
</script>