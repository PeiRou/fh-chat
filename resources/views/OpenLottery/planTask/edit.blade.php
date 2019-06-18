<form id="dataForm" class="ui mini form" action="{{ route('chat.planTask.edit',['id'=>$iInfo->id]) }}">

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
</script>