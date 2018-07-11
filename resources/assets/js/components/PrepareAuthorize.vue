<template>
<div></div>
</template>
<script>
    export default {
        mounted() {
            // window.postMessage(JSON.stringify({"userVerify": "check_access"}));
            this.userVerify();
        },
        methods: {
            userVerify(authKey) {
                if (!authKey) {
                    authKey = 'MS92L0lwL3Z6VHVXSHZsTGprNGNsUT09';
                }
                let stat = location.search.substr(1);
                window.location.href = '/clients/oauth2/redirect?' + stat + '&stat=' + authKey;
            }
        },
    }
</script>