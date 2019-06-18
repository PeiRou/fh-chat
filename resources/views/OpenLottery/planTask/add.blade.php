<form id="dataForm" class="ui mini form" action="{{ route('chat.planTask.add') }}">

    <div class="field">
        <label>游戏</label>
        <div class="ui input icon">
            <select class="ui fluid dropdown" name="game_id" id="game_id">
                <option value="">请选择游戏</option>
                @foreach($aData['game']  as $iGame)
                    <option value="{{ $iGame->game_id }}" data-category="{{ $iGame->category }}">{{ $iGame->game_name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="field">
        <label>玩法类型</label>
        <div class="ui input icon">
            <select class="ui fluid dropdown" name="type" id="type">
                <option value="">请选择玩法类型</option>
            </select>
        </div>
    </div>

    <div class="field">
        <label>玩法</label>
        <div class="ui input icon">
            <input type="text" name="play_name" id="play_name" style="height: 38px;"/>
        </div>
    </div>

    <div class="field" id="num_div" style="display: none">
        <label>选取号码</label>
        <div class="ui input icon">
            <input type="text" name="num_digits" id="num_digits" style="height: 38px;"/>
        </div>
    </div>

    <div class="field">
        <label>计划中奖概率(%)</label>
        <div class="ui input icon">
            <input type="text" name="planned_probability" id="planned_probability" style="height: 38px;"/>
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
    $('#game_id').click('change',function () {
        var category = $(this).find("option:selected").attr('data-category');
        var html = '<option value="">请选择玩法类型</option>';
        if(category === 'car'){
            html = '<option value="1">定位胆</option>';
        }else if(category === 'ssc'){
            html = '<option value="1">定位胆</option>';
        }else if(category === 'k3'){
            html = '<option value="2">和值类</option>';
        }
        $('#type').html(html);
        isType();
    });

    function isType() {
        var type = $('#type').find("option:selected").val();
        if(type == 1){
            $('#num_div').show();
        }else if(type == 2){
            $('#num_div').hide();
        }
    }
</script>