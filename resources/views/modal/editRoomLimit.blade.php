<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/updRoomInfo') }}">
    <div class="field">
        <label>房间名称</label>
        <div class="ui input icon">
            <input type="text" name="roomName"  placeholder="" value="{{ $name }}"/>
        </div>
    </div>
    <div class="field">
        <label>房间类型</label>
        <div class="ui input icon">
            @if($roomType == 1)
                <input type="text" name=""  placeholder="" value="{{$roomTypes[1]}}" disabled="disabled"/>
            @else
            <select name="roomType">
                    @foreach($roomTypes as $item => $itemname)
                        @if($item == $roomType)
                            <option  value="{{ $item }}" selected="selected">{{ $itemname }}</option>
                        @elseif($item != 1)
                            <option  value="{{ $item }}">{{ $itemname }}</option>
                        @endif
                    @endforeach
            </select>
            @endif
        </div>
    </div>
    <div class="inline field">
        <label>计划推送游戏</label>
        <br>
        <div class="ui input icon">
            @foreach($lotterys as $item => $itemname)
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" value="{{$item}}" @if(isset($games[$item])) checked="checked" @endif>
                    <label>{{$itemname}}&nbsp;</label>
                </div>
            @endforeach
        </div>
    </div>
    <div class="field">
        <label>充值要求</label>
        <div class="ui input icon">
            <input type="text" name="rech"  placeholder="" value="{{ $rech }}"/>
        </div>
    </div>
    <div class="field">
        <label>打码要求</label>
        <div class="ui input icon">
            <input type="text" name="bet"  placeholder="" value="{{ $bet }}"/>
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
                roomName: {
                    validators: {
                        notEmpty: {
                            message: '房间名称不能为空'
                        }
                    }
                },
                rech: {
                    validators: {
                        notEmpty: {
                            message: '充值不可为空'
                        }
                    }
                },
                bet: {
                    validators: {
                        notEmpty: {
                            message: '打码不能为空'
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