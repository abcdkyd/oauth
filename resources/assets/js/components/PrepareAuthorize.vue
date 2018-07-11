<template>
<div></div>
</template>
<script>
    export default {
        mounted() {
            alert('mounted');
            let dataJson = {
                type : 'accountManage'
            }
            let str = JSON.stringify(dataJson)
            window.parent.postMessage(str, '*');
            this.postMessage();
        },
        methods: {
            postMessage() {
                setTimeout(function() {
                    let dataJson = {
                        type : 'accountManage'
                    }
                    let str = JSON.stringify(dataJson)
                    window.postMessage(str, '*');
                }, 500);
            },
            userVerify(authKey) {
                let stat = location.search.substr(1);
                window.location.href = '/clients/oauth2/redirect?' + stat + '&stat=' + authKey;
            },
        },
    }
</script>