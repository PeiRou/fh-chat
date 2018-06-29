/**
 * Created by vincent on 2018/1/23.
 */
$(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    //获取当前在线人数
    // checkOnline();
});

jconfirm.defaults = {
    theme: 'material',
    animateFromElement: false,
    animation: 'zoom',
    useBootstrap: false,
    boxWidth: '30%',
    draggable: false
};

$('.nav-item>a').on('click',function(){
    if (!$('.nav').hasClass('nav-mini')) {
        if ($(this).next().css('display') == "none") {
            $('.nav-item').children('ul').slideUp(300);
            $(this).next('ul').slideDown(300);
            $(this).parent('li').addClass('nav-show').siblings('li').removeClass('nav-show');
        }else{
            $(this).next('ul').slideUp(300);
            $('.nav-item.nav-show').removeClass('nav-show');
        }
    }
});
//加上必填的＊符号
$('.notEmpty').each(function () {
    $(this).prepend('<span style="color: red">* </span>');
});

function Calert(content,color) {
    $.alert({
        icon: 'warning sign icon',
        type: color,
        title: '提示',
        content: content,
        boxWidth: '20%',
        buttons: {
            ok: {
                text:'确定',
                action: function () {
                    window.location.reload();
                }
            }
        }
    });
}

function loader(d) {
    if(d === true){
        $('.loading-mask').fadeIn();
    }else{
        $('.loading-mask').fadeOut();
    }
}

function refreshTable(table) {
    $('#'+table).DataTable().ajax.reload(null,false);
}

function Cmodal(title,boxWidth,url,needValidate,valiForm) {
    if(needValidate === true) {
        var formSubmit = {
            text:'确定提交',
            btnClass: 'btn-blue',
            action: function () {
                var form = this.$content.find('#'+valiForm).data('formValidation').validate().isValid();
                if(!form){
                    return false;
                }
                return false;
            }
        }
    } else {
        var formSubmit = {
            text:'关闭'
        }
    }
    jc = $.confirm({
        theme: 'material',
        title: title,
        closeIcon:true,
        boxWidth:boxWidth,
        content: 'url:'+url,
        buttons: {
            formSubmit: formSubmit
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

//获取当前在线人数 onlineCount
function checkOnline() {
    setInterval(function () {
        $.ajax({
            url:'/status/notice/online',
            type:'get',
            dataType:'json',
            success:function (data) {
                if(data.status === true){
                    $('#onlineCount').html(data.count);
                    console.log('num:'+data.count);
                }
            }
        })
    },3000)
}

function logout() {
    $.confirm({
        title: '确定退出？',
        theme: 'material',
        type: 'orange',
        boxWidth:'20%',
        content: '我们强烈建议，如果您无需使用系统，请务必退出当前账号！',
        buttons: {
            confirm: {
                text:'退出',
                btnClass: 'btn-orange',
                action: function(){
                    $.ajax({
                        url:'/chat/admin/logout',
                        type:'GET',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true)
                            {
                                location.href='/'
                            }
                        }
                    });
                    return false;
                }
            },
            cancel:{
                text:'取消'
            }
        }
    });
}

function autoLogout() {
    $.ajax({
        url:'/chat/admin/logout',
        type:'GET',
        dataType:'json',
        success:function (data) {
            if(data.status == true)
            {
                location.href='/'
            }
        }
    });
}

function del(id,funcName) {
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要删除这条数据?',
        buttons: {
            confirm: {
                text:'确定删除',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/'+funcName+'/'+id,
                        type:'post',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true){
                                $('#dtTable').DataTable().ajax.reload(null,false)
                            }
                        },
                        error:function (e) {
                            if(e.status == 403)
                            {
                                Calert('您没有此项权限！无法继续！','red')
                            }
                        }
                    });
                }
            },
            cancel:{
                text:'取消'
            }
        }
    });
}