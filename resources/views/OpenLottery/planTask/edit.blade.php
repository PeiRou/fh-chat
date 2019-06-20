<form id="dataForm" class="ui mini form" action="{{ route('chat.planTask.edit',['id'=>$iInfo->id]) }}">
    <div class="field">
        <label>计划名称显示</label>
        <div class="ui input icon">
            <input type="text" name="play_name" id="play_name" style="height: 38px;" value="{{ $iInfo->play_name }}"/>
        </div>
    </div>

    <div class="field">
        <label>计划中奖概率(%)</label>
        <div class="ui input icon">
            <input type="text" name="planned_probability" id="planned_probability" style="height: 38px;" value="{{ $iInfo->planned_probability }}"/>
        </div>
    </div>
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
            play_name:{
                validators: {
                    notEmpty: {
                        message: '游戏不能为空'
                    }
                }
            },
            planned_probability:{
                validators: {
                    notEmpty: {
                        message: '计划中奖概率不能为空'
                    },
                    greaterThan: {
                        value: 0,
                        message: '数字请控制在0~100之间'
                    },
                    lessThan: {
                        value: 100,
                        message: '数字请控制在0~100之间'
                    }
                }
            }
        }
    }).on('success.form.fv', function(e) {
        e.preventDefault();
        var $form = $(e.target),
        fv = $form.data('formValidation');
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
</script>