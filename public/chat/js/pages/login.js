$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
});

function login() {
    var userName = $("#userName").val();
    var userPwd = $("#userPwd").val();
    var captcha = $('#cap').val();

    if(!userName) {
        alert("请输入用户名");
        return false;
    }

    if(!userPwd) {
        alert("请输入密码");
        return false;
    }

    if(!captcha) {
        alert("请输入验证码");
        return false;
    }

    $.ajax({
        type: 'POST',
        url: '/chat/admin/login',
        data: {
            account: userName,
            pwdtext: userPwd,
            password: userPwd,
            captcha: captcha
        },
        dataType: 'json',
        success: function(result) {
            if(result.status === false){
                alert(result.msg || "系统错误");
            } else {
                location.href = '/chat/dash';
            }
        },
        error: function(result) {
            alert(result.msg || "系统错误");
        }
    });
}