<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/updForbidInfo') }}">
    <div class="field">
        <label>违禁词</label>
        <div class="ui input icon">
            <input type="text" name="regex"  placeholder="" value="{{ $regex }}" />
        </div>
    </div>

    <input type="hidden" value="{{ $id }}" name="id">
    <input type="hidden" value="{{ $roomid }}" name="roomid">
</form>

<script>
    $('#updUserForm').formValidation({
            framework: 'semantic',
            icon: {
                valid: 'checkmark icon',
                invalid: 'remove icon',
                validating: 'refresh icon'
            },
            fields: {
                regex: {
                    validators: {
                        notEmpty: {
                            message: '违禁词不能为空'
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
                    Calert(result.msg,'red');
                }
            }
        });
    });
</script>