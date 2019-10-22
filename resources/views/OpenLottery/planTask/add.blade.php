<form id="dataForm" class="ui mini form" action="{{ route('chat.planTask.add') }}">

    <div class="field">
        <label>游戏</label>
        <div class="ui input icon">
            <select class="ui fluid dropdown" name="game_id" id="game_id">
                <option value="">请选择游戏</option>
                <optgroup label="赛车、飞艇、跑马">
                    @foreach($aData['game']  as $iGame)
                        @if(($iGame->category == 'car'))
                            <option value="{{ $iGame->game_id }}" data-category="{{ $iGame->category }}">{{ $iGame->game_name }}</option>
                        @endif
                    @endforeach
                </optgroup>
                <optgroup label="时时彩">
                    @foreach($aData['game']  as $iGame)
                        @if(($iGame->category == 'ssc'))
                            <option value="{{ $iGame->game_id }}" data-category="{{ $iGame->category }}">{{ $iGame->game_name }}</option>
                        @endif
                    @endforeach
                </optgroup>
                <optgroup label="快三">
                    @foreach($aData['game']  as $iGame)
                        @if(($iGame->category == 'k3'))
                            <option value="{{ $iGame->game_id }}" data-category="{{ $iGame->category }}">{{ $iGame->game_name }}</option>
                        @endif
                    @endforeach
                </optgroup>
                <optgroup label="六合彩">
                    @foreach($aData['game']  as $iGame)
                        @if(($iGame->category == 'lhc'))
                            <option value="{{ $iGame->game_id }}" data-category="{{ $iGame->category }}">{{ $iGame->game_name }}</option>
                        @endif
                    @endforeach
                </optgroup>
                <optgroup label="快乐八">
                    @foreach($aData['game']  as $iGame)
                        @if(($iGame->category == 'kl8'))
                            <option value="{{ $iGame->game_id }}" data-category="{{ $iGame->category }}">{{ $iGame->game_name }}</option>
                        @endif
                    @endforeach
                </optgroup>
            </select>
        </div>
    </div>

    <div class="field">
        <label>玩法类型</label>
        <div class="ui input icon">
            <select class="ui fluid dropdown" name="type" id="type">
                <option value="">请选择玩法类型</option>
            </select>
        </div>
    </div>

    <div class="field" id="num_div">
        <label>选取号码</label>
        <div class="ui input icon">
            <select class="ui fluid dropdown" name="num_digits" id="num_digits">
                <option value="">请选取号码</option>
            </select>
        </div>
    </div>

    <div class="field">
        <label>前台显示名称</label>
        <div class="ui input icon">
            <input type="text" name="play_name" id="play_name" style="height: 38px;"/>
        </div>
    </div>

    <div class="field" id="play_name_div">
        <label>计划个数</label>
        <div class="ui input icon">
            <input type="text" name="plan_num" id="plan_num" style="height: 38px;"/>
        </div>
    </div>

    {{--<div class="field">--}}
        {{--<label>计划中奖概率(%)</label>--}}
        {{--<div class="ui input icon">--}}
            {{--<input type="text" name="planned_probability" id="planned_probability" style="height: 38px;"/>--}}
        {{--</div>--}}
    {{--</div>--}}

</form>
<script>

    $('#dataForm').formValidation({
        framework: 'semantic',
        icon: {
            valid: 'checkmark icon',
            invalid: 'remove icon',
            validating: 'refresh icon'
        },
        fields: {
            game_id:{
                validators: {
                    notEmpty: {
                        message: '游戏不能为空'
                    }
                }
            },
            play_name:{
                validators: {
                    notEmpty: {
                        message: '前台显示名称不能为空'
                    }
                }
            },
            plan_num:{
                validators: {
                    notEmpty: {
                        message: '计划个数不能为空'
                    },
                    greaterThan: {
                        value: 1,
                        message: '数字请控制在1~8之间'
                    },
                    lessThan: {
                        value: 8,
                        message: '数字请控制在1~8之间'
                    }
                }
            }
            // planned_probability:{
            //     validators: {
            //         notEmpty: {
            //             message: '计划中奖概率不能为空'
            //         },
            //         greaterThan: {
            //             value: 0,
            //             message: '数字请控制在0~100之间'
            //         },
            //         lessThan: {
            //             value: 100,
            //             message: '数字请控制在0~100之间'
            //         }
            //     }
            // }
        }
    }).on('success.form.fv', function(e) {
        e.preventDefault();
        var $form = $(e.target),
            fv    = $form.data('formValidation');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            dataType:'json',
            data: $form.serialize(),
            success: function(result) {
                if(result.status == true){
                    jc.close();
                    $('#tableData').DataTable().ajax.reload(null,false);
                }else{
                    alert(result.msg);
                }
            }
        });
    });
    $('#game_id').on('change',function () {
        var category = $(this).find("option:selected").attr('data-category');
        var html = '<option value="">请选择玩法类型</option>';
        if(category === 'car'){
            html = '<option value="1">定位胆</option>';
            html += '<option value="11">定位胆大小</option>';
        }else if(category === 'ssc'){
            html = '<option value="1">定位胆</option>';
        }else if(category === 'k3'){
            html = '<option value="2">和值类</option>';
        }else if(category === 'lhc'){
            html = '<option value="3">平特码生肖</option>';
        }
        $('#type').html(html);
        isType();
    });


    //选取号码
    $('#game_id').on('change',function () {
        var category = $(this).find("option:selected").attr('data-category');
        var html = '<option value="">请选取号码</option>';
        if(category === 'car'){
            html = '<option value="1">冠军</option>';
            html+= '<option value="2">亚军</option>';
            html+= '<option value="3">季军</option>';
            html+= '<option value="4">第四名</option>';
            html+= '<option value="5">第五名</option>';
            html+= '<option value="6">第六名</option>';
            html+= '<option value="7">第七名</option>';
            html+= '<option value="8">第八名</option>';
            html+= '<option value="9">第九名</option>';
            html+= '<option value="10">第十名</option>';
        }else if(category === 'ssc'){
            html = '<option value="1">万位</option>';
            html+= '<option value="2">千位</option>';
            html+= '<option value="3">百位</option>';
            html+= '<option value="4">十位</option>';
            html+= '<option value="5">个位</option>';
        }else if(category === 'k3' || category === 'lhc'){
            html = '<option value=""></option>';
        }
        $('#num_digits').html(html);
        isType();
    });

    //前台显示名称
    $('#num_digits').on('change',function () {
        var num_digits = $(this).find("option:selected").text();
            $('#play_name').val(num_digits);
    });
    $('#type').on('change',function () {
        isType();
    });


    function isType() {
        var type = $('#type').find("option:selected").val();
        var num_digits = $('#num_digits').find("option:selected").text();
        $('#play_name_div').show();
        if(type == 1){
            $('#num_div').show();
            $('#play_name').val(num_digits);
        }else if(type == 11){
            $('#num_div').show();
            $('#plan_num').val('1');
            $('#play_name_div').hide();
            $('#play_name').val(num_digits+'大小');
        }else if(type == 2){
            $('#num_div').hide();
            $('#play_name').val('总和');
        }else if(type == 3){
            $('#num_div').hide();
            $('#play_name').val('平特码生肖');
        }else{
            $('#play_name').val('');
        }
    }
</script>