<template>
<div></div>
</template>
<script>
    export default {
        mounted() {
            window.postMessage(JSON.stringify({"userVerify": "check_access"}));
            this.userVerify();
        },
        methods: {
            userVerify(authKey) {
                let stat = location.search.substr(1);
                window.location.href = '/clients/oauth2/redirect?' + stat + '&stat=' + authKey;
            }
        },
    }
</script>