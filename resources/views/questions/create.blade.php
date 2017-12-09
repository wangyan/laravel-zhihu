@extends('layouts.app')

@section('content')
    @include('vendor.ueditor.assets')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">发布问题</div>
                    <div class="panel-body">
                        @include('flash::message')
                        <form action="/questions" method="post">
                            {!! csrf_field() !!}
                            <div class="form-group">
                                <label for="title">标题</label>
                                <input type="text" name="title" class="form-control" placeholder="标题" class="title">
                            </div>
                            <script id="container" name="body" type="text/plain"></script><br>
                            <button type="submit" class="btn btn-block btn-success pull-right">发布问题</button>
                        </form>
                    </div>
                    <script type="text/javascript">
                        var ue = UE.getEditor('container');
                        ue.ready(function() {
                            ue.execCommand('serverparam', '_token', '{{ csrf_token() }}');
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection