<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/updRoleInfo') }}">
    <div class="field">
        <label>角色名</label>
            <div class="ui input icon">
                {{--<input type="text" name="roleName"  placeholder="" value="{{ $role->name }}" @if($role->name !="" && ($role->level == 0 || $role->level == 1 || $role->level == 99)) disabled="disabled" @endif/>--}}
                <input type="text" name="roleName"  placeholder="" value="{{ $role->name }}" disabled="disabled"/>
            </div>
    </div>
    <div class="field">
        <label>角色类型</label>
        <div class="ui input icon disabled">
            <select name="level" @if($role->name !="" && ($role->level == 0 || $role->level == 1 || $role->level == 98 || $role->level == 99)) disabled="disabled" @endif>
                <option value="0" @if($role->level == 0) selected="selected" @endif>游客</option>
                <option value="99" @if($role->level == 98) selected="selected" @endif>计划消息</option>
                <option value="99" @if($role->level == 99) selected="selected" @endif>管理员</option>
                @foreach($role_level as $item )
                    <option value="{{$item->id}}" @if($item->id == $role->level) selected="selected" @endif>{{$item->levelname}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="field">
        <label>聊天背景颜色</label>
        <div class="ui input" style="width:auto">
            <input type="text" name="bg1" class="jscolor" value="{{ $role->bg1 }}" style="width:100px"/>
        </div>
        <div class="ui input" style="width:auto">
            <input type="text" name="bg2" class="jscolor" value="{{ $role->bg2 }}" style="width:100px" placeholder="留白不要颜色"/>
        </div>
    </div>
    <div class="field">
        <label>字体颜色</label>
        <div class="ui input icon mini">
            <input type="text" name="font" class="jscolor" value="{{ $role->font }}"/>
        </div>
    </div>
    {{--<div class="field">--}}
        {{--<label>消息最大长度</label>--}}
        {{--<div class="ui input icon">--}}
            {{--<select name="length">--}}
                {{--<option value="" @if($role->length == "") selected="selected" @endif>不限制</option>--}}
                {{--<option value="100" @if($role->length == 100) selected="selected" @endif>100</option>--}}
                {{--<option value="200" @if($role->length == 200) selected="selected" @endif>200</option>--}}
                {{--<option value="300" @if($role->length == 300) selected="selected" @endif>300</option>--}}
                {{--<option value="500" @if($role->length == 500) selected="selected" @endif>500</option>--}}
                {{--<option value="1000" @if($role->length == 1000) selected="selected" @endif>1000</option>--}}
            {{--</select>--}}
        {{--</div>--}}
    {{--</div>--}}
    <div class="field">
        <label>角色权限</label>
        <div class="ui checkbox disabled">
            <input type="checkbox" name="permiss1" @foreach($permiss as $item => $val) @if($val == 1) checked="checked"  @endif @endforeach><label>发言</label>
        </div>
        <div class="ui checkbox disabled">
            <input type="checkbox" name="permiss2" @foreach($permiss as $item => $val) @if($val == 2) checked="checked"  @endif @endforeach><label>发图</label>
        </div>
        <div class="ui checkbox disabled">
            <input type="checkbox" name="permiss3" @foreach($permiss as $item => $val) @if($val == 3) checked="checked"  @endif @endforeach><label>踢人</label>
        </div>
        <div class="ui checkbox disabled">
            <input type="checkbox" name="permiss4" @foreach($permiss as $item => $val) @if($val == 4) checked="checked"  @endif @endforeach><label>禁言</label>
        </div>
    </div>
    {{--<div class="field">--}}
        {{--<label>描述</label>--}}
        {{--<div class="ui input icon">--}}
            {{--<textarea name="content" rows="3">{{ $role->description }}</textarea>--}}
        {{--</div>--}}
    {{--</div>--}}

    <input type="hidden" value="{{ $role->id }}" name="id">
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
                bg_color1: {
                    validators: {
                        notEmpty: {
                            message: '左边的聊天背景颜色不可留白'
                        }
                    }
                },
                font_color: {
                    validators: {
                        notEmpty: {
                            message: '字体颜色不可留白'
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

    $('.jscolor').each(function () {
        var picker = new jscolor(this);
        picker.fromString($(this).val());
    });
</script>