/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-baseManage').addClass('active');

    //开始时间
    $('#starttime').calendar({
        ampm: false,
        type: 'time'
    });
    //结束时间
    $('#endtime').calendar({
        ampm: false,
        type: 'time'
    });
    $('#chkOpenStatus').change(function () {
        if($(this).prop( "checked" )==true){
            $('#dvOpenStatusOn').show();
            $('#dvOpenStatusUn').hide();
        }else{
            $('#dvOpenStatusUn').show();
            $('#dvOpenStatusOn').hide();
        }
    });
    $('#bet_push_status').change(function () {
        if($(this).prop( "checked" )==true){
            $('#bet_push_statusOn').show();
            $('#bet_push_statusUn').hide();
        }else{
            $('#bet_push_statusUn').show();
            $('#bet_push_statusOn').hide();
        }
    });
    if($('#planSendMode').val()==0){
        $('#dspPlan').show();
    }
    //如果选择手动发布
    $('#planSendMode').change(function(){
        if($(this).val()==0)
            $('#dspPlan').show();
        else
            $('#dspPlan').hide();
    });
    $('#dspPlan').click(function () {
        manual();
    });
});
$('#dspPlan').click(function () {
   console.log(1);
});
$('#updUserForm').formValidation({
    framework: 'semantic',
    icon: {
        valid: 'checkmark icon',
        invalid: 'remove icon',
        validating: 'refresh icon'
    },
    fields: {
        starttime: {
            validators: {
                notEmpty: {
                    message: '发布时段(开始)不能为空'
                },
                regexp: {
                    regexp: /^([0-9]|1[0-9]|2[0-3]{1}):[0-5]{1}[0-9]{1}$/i,
                    message: '发布时段(开始)必须输入例如9:00'
                }
            }
        },
        endtime: {
            validators: {
                notEmpty: {
                    message: '发布时段(结束)不能为空'
                },
                regexp: {
                    regexp: /^([0-9]|1[0-9]|2[0-3]{1}):[0-5]{1}[0-9]{1}$/i,
                    message: '发布时段(结束)必须输入例如2:00'
                }
            }
        },
        betMin: {
            validators: {
                notEmpty: {
                    message: '下注最低推送额不能为空'
                },
                integer: {
                    message: '下注最低推送额必须输入整数',
                    thousandsSeparator: '',
                    decimalSeparator: '.'
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
                Calert("操作成功",'green','操作提示');
            } else {
                Calert(result.msg,'red');
            }
        }
    });
});
//手动发送计画任务
function manual() {
    jc = $.confirm({
        theme: 'material',
        title: '手动发送计画',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/manualPlan',
        buttons: {
            confirm: {
                text: '提交',
                btnClass: 'btn-blue',
                action: function () {
                    var form = this.$content.find('#updForm').data('formValidation').validate().isValid();
                    if(!form){
                        return false;
                    }
                    return false;
                }
            },
            cancel: {
                text:'关闭'
            }
        },
        contentLoaded: function(data, status, xhr){
            $('.jconfirm-content').css('overflow','hidden');
            if(xhr == 'Forbidden')
            {
                this.setContent('<div class="modal-error"><span class="error403">403</span><br><span>您无权进行此操作</span></div>');
                $('.jconfirm-buttons').hide();
            }
        }
    });
}