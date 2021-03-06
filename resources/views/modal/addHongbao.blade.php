<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/addHongbao') }}">
    <div class="field">
        <label>选择房间</label>
        <div class="ui input icon">
            <select name="room">
                @foreach($room as $item )
                    <option value="{{$item->roomid}}" @if($item->roomid == 1) selected="selected" @endif>{{$item->room_name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="field">
        <label>类型</label>
        <div class="ui input icon">
            <select name="type" id="type">
                <option value="0">类型1</option>
                <option value="1">类型2</option>
            </select>
        </div>
    </div>
    <div class="type0 type">
        <div class="field">
            <label>红包总金额</label>
            <div class="ui input icon">
                <input type="text" name="hongbao_total_amount"  placeholder="" value=""/>
            </div>
        </div>
    </div>
    <div class="field">
        <label>红包总个数</label>
        <div class="ui input icon">
            <input type="text" name="hongbao_total_num"  placeholder="" value=""/>
        </div>
    </div>
    <div class="type1 type">

        <div class="field">
            <label>红包最小金额</label>
            <div class="ui input icon">
                <input type="text" name="hongbao_min_amount"  placeholder="" value=""/>
            </div>
        </div>
        <div class="field">
            <label>红包最大金额</label>
            <div class="ui input icon">
                <input type="text" name="hongbao_max_amount"  placeholder="" value=""/>
            </div>
        </div>
    </div>
    <div class="field">
        <label>最低充值金额</label>
        <div class="ui input icon">
            <input type="text" name="recharge"  placeholder="如不限制条件，请填写0" value=""/>
        </div>
    </div>
    <div class="field">
        <label>最低下注金额</label>
        <div class="ui input icon">
            <input type="text" name="bet"  placeholder="如不限制条件，请填写0" value=""/>
        </div>
    </div>
</form>

<script>
    $(function(){
        $('#type').change(function(){
            s();
        })
        s();
    })

    function s(){
        $('.type').hide();
        $('.type'+$('#type').val()).show();
    }
    $('#updUserForm').formValidation({
            framework: 'semantic',
            icon: {
                valid: 'checkmark icon',
                invalid: 'remove icon',
                validating: 'refresh icon'
            },
            fields: {
                hongbao_total_amount: {
                    validators: {
                        notEmpty: {
                            message: '红包总金额不能为空'
                        },
                        digits: {
                            message: '请输入数字'
                        }
                    }
                },
                hongbao_total_num: {
                    validators: {
                        notEmpty: {
                            message: '红包总个数不能为空'
                        },
                        digits: {
                            message: '请输入数字'
                        }
                    }
                },
                recharge: {
                    validators: {
                        notEmpty: {
                            message: '最低充值金额不能为空'
                        },
                        digits: {
                            message: '请输入数字'
                        }
                    }
                },
                bet: {
                    validators: {
                        notEmpty: {
                            message: '最低下注金额不能为空'
                        },
                        digits: {
                            message: '请输入数字'
                        }
                    }
                }
            }
        }).on('success.form.fv', function(e) {
        e.preventDefault();
        var $form = $(e.target),
            fv    = $form.data('formValidation');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function(result) {
                if(result.status == true){
                    jc.close();
                    $('#dtTable').DataTable().ajax.reload(null,false);
                } else {
                    if("{{!env('TEST')}}") Calert(result.msg,'red') // 开发暂时注释
                    else layer.msg(result.msg);
                }
            }
        });
    });
</script>