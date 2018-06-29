<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/updUserInfo') }}">
    <div class="field">
        <label>选择角色</label>
        <div class="ui input icon">
            <select name="level">
                @foreach($roles as $item)
                    @if($item->level == $level)
                        <option  value="{{ $item->level }}" selected="selected">{{ $item->name }}</option>
                    @else
                        <option  value="{{ $item->level }}">{{ $item->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>
    <div class="field">
        <label>是否不自动计算层级</label>
        <div class="ui checkbox">
            <input type="checkbox" name="auto_count" @if($unauto == 1) checked="checked"  @endif>
            <label>是</label>
        </div>
    </div>

    <input type="hidden" value="{{ $id }}" name="id">
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
                role: {
                    validators: {
                        notEmpty: {
                            message: '参数错误'
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