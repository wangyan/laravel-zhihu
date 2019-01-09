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
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <label for="title">标题</label>
                                <input type="text" name="title" class="form-control" placeholder="标题" value="{{ old('title') }}" required autofocus>
                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="topic">话题</label>
                                <select name="topic[]" class="select2-placeholder-multiple form-control js-data-example-ajax" multiple="multiple">
                                </select>
                            </div>
                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                                <script id="container" style="height: 200px" name="body" type="text/plain">
                                    {!! old('body') !!}
                                </script><br/>
                                @if ($errors->has('body'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('body') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-block btn-success pull-right">发布问题</button>
                        </form>
                    </div>
                    <script type="text/javascript">
                        var ue = UE.getEditor('container', {
                            toolbars: [
                                ['bold', 'italic', 'underline', 'strikethrough', 'blockquote', 'insertunorderedlist', 'insertorderedlist', 'justifyleft','justifycenter', 'justifyright',  'link', 'insertimage', 'fullscreen']
                            ],
                            elementPathEnabled: false,
                            enableContextMenu: false,
                            autoClearEmptyNode:true,
                            wordCount:false,
                            imagePopup:false,
                            autotypeset:{ indent: true,imageBlockLine: 'center' }
                        });
                        ue.ready(function() {
                            ue.execCommand('serverparam', '_token', '{{ csrf_token() }}');
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script type="text/javascript">
    $(document).ready(function() {
        function formatTopic (topic) {
            return  "<div class='select2-result-repository clearfix'>" +
            "<div class='select2-result-repository__meta'>" +
            "<div class='select2-result-repository__title'>" +
            topic.name ? topic.name : "Laravel"   +
                "</div></div></div>";
        }
        function formatTopicSelection (topic) {
            return topic.name || topic.text;
        }
        $(".select2-placeholder-multiple").select2({
            language: "zh-CN",
            tags: true,
            placeholder: '选择相关话题',
            minimumInputLength: 1,
            ajax: {
                url: '/api/topics',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                    };
                },
                processResults: function (data) {
                    return {
                        results: data,
                    };
                },
                cache: true
            },
            templateResult: formatTopic,
            templateSelection: formatTopicSelection,
            escapeMarkup: function (markup) { return markup; }
        });
    });
</script>
@endsection