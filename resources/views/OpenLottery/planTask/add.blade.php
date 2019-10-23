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
            html += '<option value="12">定位胆单双</option>';
            html += '<option value="13">冠亚大小</option>';
            html += '<option value="14">冠亚单双</option>';
        }else if(category === 'ssc'){
            html = '<option value="1">定位胆</option>';
            html += '<option value="11">定位胆大小</option>';
            html += '<option value="12">定位胆单双</option>';
            html += '<option value="21">总和大小</option>';
            html += '<option value="22">总和单双</option>';
        }else if(category === 'k3'){
            html = '<option value="2">总和</option>';
            html += '<option value="21">总和大小</option>';
            html += '<option value="22">总和单双</option>';
        }else if(category === 'lhc'){
            html = '<option value="1">定位胆</option>';
            html += '<option value="3">平特码生肖</option>';
        }
        $('#type').html(html);
        isType();
    });


    //选取号码
    $('#game_id').on('change',function () {
        var type = $('#type').find("option:selected").val();
        var category = $(this).find("option:selected").attr('data-category');
        var html = '<option value="">请选取号码</option>';
        if(category === 'car' && (type ==1 ||type ==11 ||type ==12)){
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
        }else if(category === 'ssc' && (type ==1 || type ==11 || type ==12)){
            html = '<option value="1">万位</option>';
            html+= '<option value="2">千位</option>';
            html+= '<option value="3">百位</option>';
            html+= '<option value="4">十位</option>';
            html+= '<option value="5">个位</option>';
        }else if(category === 'lhc' && type ==1){
            html = '<option value="1">正码一</option>';
            html += '<option value="2">正码二</option>';
            html += '<option value="3">正码三</option>';
            html += '<option value="4">正码四</option>';
            html += '<option value="5">正码五</option>';
            html += '<option value="6">正码六</option>';
            html += '<option value="7">特码</option>';
        }else{
            html = '<option value=""></option>';
        }
        $('#num_digits').html(html);
        isType();
    });

    //前台显示名称
    $('#num_digits').on('change',function () {
        isType();
    });
    $('#type').on('change',function () {
        isType();
    });


    function isType() {
        var type = $('#type').find("option:selected").val();
        var num_digits = $('#num_digits').find("option:selected").text();
        $('#play_name_div').hide();
        $('#num_div').hide();
        $('#play_name').val('');
        //选取号码是否显示
        switch (type) {
            case '1':
            case '11':
            case '12':
                $('#num_div').show();   //选取号码是否显示
                break;
        }
        //计划个数是否显示
        switch (type) {
            case '1':
            case '2':
            case '3':
                $('#play_name_div').show();   //计划个数要显示
                break;
            default:
                $('#plan_num').val('1');
                break;
        }
        //前台显示名称
        switch (type) {
            case '1':
                $('#play_name').val(num_digits);
                break;
            case '11':
                $('#play_name').val(num_digits+'大小');
                break;
            case '12':
                $('#play_name').val(num_digits+'单双');
                break;
            case '13':
                $('#play_name').val('冠亚大小');
                break;
            case '14':
                $('#play_name').val('冠亚单双');
                break;
            case '21':
                $('#play_name').val('总和大小');
                break;
            case '22':
                $('#play_name').val('总和单双');
                break;
            case '3':
                $('#play_name').val('平特码生肖');
                break;
        }
    }
</script>