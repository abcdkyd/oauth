<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>用户授权</title>

    <!-- Styles -->
    <link href="/css/app.css" rel="stylesheet">

    <style>
        .passport-authorize .container {
            margin-top: 30px;
        }

        .passport-authorize .scopes {
            margin-top: 20px;
        }

        .passport-authorize .buttons {
            margin-top: 25px;
            text-align: center;
        }

        .passport-authorize .btn {
            width: 125px;
        }

        .passport-authorize .btn-approve {
            margin-right: 15px;
        }

        .passport-authorize form {
            display: inline;
        }
    </style>
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        function submit() {
            {{--jsFormSubmit({--}}
                {{--url: '/oauth/authorize',--}}
                {{--client_id: {{$client['id']}},--}}
                {{--redirect_uri: '{{$client['redirect']}}',--}}
                {{--response_type: 'code',--}}
                {{--scope: ''--}}
            {{--});--}}
            let form = new FormData();
            form.append('client_id', {{$client['id']}});
            form.append('redirect_uri', '{{$client['redirect']}}');
            form.append('response_type', 'code');
            form.append('scope', '');
            axios.post('/oauth/authorize', {
                client_id: {{$client['id']}},
                redirect_uri: '{{$client['redirect']}}',
                response_type: 'code',
                scope: ''
            }, {
                headers: {
                    Authorization: 'Bearer ' + localStorage.getItem('access_token')
                }
            }).then(function (response) {
                console.log(response);
            }).error(function (error) {
                console.log(error);
            });
            // let req = new XMLHttpRequest();
            // req.open("post", "/oauth/authorize", false);
            // req.send(form);
            {{--let form = new FormData();--}}
            {{--form.append('client_id', {{$client['id']}});--}}
            {{--form.append('redirect_uri', '{{$client['redirect']}}');--}}
            {{--form.append('response_type', 'code');--}}
            {{--form.append('scope', '');--}}
            {{--$.ajax({--}}
                {{--url:"/oauth/authorize",--}}
                {{--type:"post",--}}
                {{--data:form,--}}
                {{--async: false,--}}
                {{--dataType: "json",--}}
                {{--headers: {--}}
                    {{--Authorization: 'Bearer ' + localStorage.getItem('access_token'),--}}
                {{--},--}}
                {{--processData:false,--}}
                {{--contentType:'application/x-www-form-urlencoded',--}}
                {{--success:function(data){--}}
                    {{--console.log("over..");--}}
                {{--}--}}
            {{--});--}}
        }
    </script>
</head>
<body class="passport-authorize">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-default">
                    <div class="card-header">
                        普惠通登录
                    </div>
                    <div class="card-body">
                        <!-- Introduction -->
                        <p><strong>{{ $client['name'] }}</strong>正在请求允许访问您的帐户</p>

                        <!-- Scope List -->
                        @if (count($scopes) > 0)
                            <div class="scopes">
                                    <p><strong>向其提供以下权限即可继续操作:</strong></p>

                                    <ul>
                                        @foreach ($scopes as $scope)
                                            <li>{{ $scope['description'] }}</li>
                                        @endforeach
                                    </ul>
                            </div>
                        @endif

                        <div class="buttons">
                            <!-- Authorize Button -->
                            <form method="post" action="/oauth/authorize">
                                {{ csrf_field() }}

                                <input type="hidden" name="state" value="{{ $request->state }}">
                                <input type="hidden" name="client_id" value="{{ $client['id'] }}">
                                {{--<input type="hidden" name="response_type" value="code">--}}
                                {{--<input type="hidden" name="redirect_uri" value="{{ $client['redirect'] }}">--}}
                                {{--<input type="hidden" name="scope" value="">--}}
                                {{--<input type="hidden" name="access_token" id="token">--}}
                                <button type="submit" class="btn btn-success btn-approve">确认登录</button>
                            </form>
                            <!-- Cancel Button -->
                            {{--<form method="get" action="/oauth/authorize">--}}
                                {{--{{ csrf_field() }}--}}
                                {{--{{ method_field('DELETE') }}--}}

                                {{--<input type="hidden" name="state" value="{{ $request->state }}">--}}
                                {{--<input type="hidden" name="client_id" value="{{ $client['id'] }}">--}}
                                {{--<button class="btn btn-danger">取消</button>--}}
                            {{--</form>--}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
