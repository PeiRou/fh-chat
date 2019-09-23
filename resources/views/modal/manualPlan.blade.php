<form id="updForm" class="ui mini form" action="{{ url('/chat/action/sendPlan') }}">
    <div class="field">
        <label>计划内容</label>
        <div class="ui input icon">
            <textarea name="plan" rows="6"></textarea>
        </div>
    </div>
</form>

<script>
    $('#updForm').formValidation({
            framework: 'semantic',
            icon: {
                valid: 'checkmark icon',
                invalid: 'remove icon',
                validating: 'refresh icon'
            },
            fields: {
                plan: {
                    validators: {
                        notEmpty: {
                            message: '计划内容不能为空'
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
                } else {
                    Calert(result.msg,'red');
                }
            }
        });
    });
</script>