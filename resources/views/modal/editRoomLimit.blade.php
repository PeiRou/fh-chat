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
                    <input type="checkbox" name="planSendGames[]" value="{{$item}}" @if(isset($games[$item])) checked="checked" @endif>
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
    <div class="field">
        <label>房间头像</label>
        <div class="ui input icon">
            <input type="file" onchange="getBase64(this)">
        </div>
        <textarea style="display: none" name="head_img" id="head_img" ></textarea>
        <img id="head_img_img" src="{{ str_replace('/upchat', '', (@$roomInfo->head_img ?? '')) ?? '' }}" alt="" style="max-height: 50px; max-width: 50px">
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
    function getBase64(e) {
        run(e,function (res) {
            $('#head_img_img').attr('src',res);
            $('#head_img').html(res);
        });
    }
    function run(input_file,get_data){
        /*input_file：文件按钮对象*/
        /*get_data: 转换成功后执行的方法*/
        if ( typeof(FileReader) === 'undefined' ){
            alert("抱歉，你的浏览器不支持 FileReader，不能将图片转换为Base64，请使用现代浏览器操作！");
        } else {
            try{
                /*图片转Base64 核心代码*/
                var file = input_file.files[0];
                if(file !== undefined) {
                    //这里我们判断下类型如果不是图片就返回 去掉就可以上传任意文件
                    if (!/image\/\w+/.test(file.type)) {
                        alert("请确保文件为图像类型");
                        return false;
                    }
                    var reader = new FileReader();
                    reader.onload = function () {
                        get_data(this.result);
                    };
                    reader.readAsDataURL(file);
                }else{
                    get_data('');
                }
            }catch (e){
                alert('图片转Base64出错啦！'+ e.toString())
            }
        }
    }
</script>