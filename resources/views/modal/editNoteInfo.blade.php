<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/updNoteInfo') }}">
    <div class="field">
        <label>房间</label>
        @if(Session::get('ISROOMS'))
            @foreach($allRooms as $item => $val)
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="rooms[]" value="{{$val->room_id}}" @if(in_array($val->room_id,$rooms))) checked="checked" @endif>
                    <label>{{$val->room_name}}&nbsp;</label>
                </div>
            @endforeach
        @else
            <div class="ui input icon">
                <input type="text" name="roomName"  placeholder="" value="{{ $name }}" disabled="disabled"/>
            </div>
            <input type="hidden" value="" name="rooms">
        @endif
    </div>
    <div class="field">
        <label>公告内容</label>
        <div class="ui input icon">
            <textarea name="content" rows="3">{{ $note->content }}</textarea>
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
                content: {
                    validators: {
                        notEmpty: {
                            message: '公告内容不能为空'
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