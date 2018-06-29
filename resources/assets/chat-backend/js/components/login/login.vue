
<template>
    <div>
        <div class="login" >
            <div class="mainBody" style="z-index: 0">
                <div class="nprogress-container"></div>
                <div class="cloud"></div>
                <div class="cloud"></div>
            </div>
            <div class="logintop">
                <span>欢迎登录聊天室后台</span>
            </div>
            <div class="loginbody" v-loading="loading" >

                <!--<form name="loginForm" class="loginbox"  :model="loginForm" ref="loginForm" :rules="loginRules">-->
                    <!--<ul>-->
                        <!--<li>-->
                            <!--<input v-model="loginForm.name" type="text" class="loginuser">-->
                            <!--<p style="margin-bottom: -10px;">-->
                                <!--<span style="color: rgb(255, 0, 0);"></span>-->
                            <!--</p>-->
                        <!--</li>-->
                        <!--<li style="margin-top: 30px;">-->
                            <!--<input type="password" v-model="loginForm.pass"  class="loginpwd">-->
                            <!--<p style="margin-bottom: -10px;">-->
                                <!--<span style="color: rgb(255, 0, 0);"></span>-->
                            <!--</p>-->
                        <!--</li>-->
                        <!--<li style="overflow: hidden;">-->
                            <!--<input @keyup.enter.native ="submitLogin('loginForm')"  v-model="loginForm.code" maxlength="4" type="text" class="logincode">-->
                            <!--<s-identify class="identify"  :identifyCode="identifyCode"></s-identify>-->
                            <!--&lt;!&ndash;<img src="/api/getValidateCode.do"  class="valid-pic">&ndash;&gt;-->
                            <!--<span @click="refreshCode" class="refreshCode" >点击刷新验证码</span>-->
                            <!--<p style="margin-bottom: -10px;">-->
                                <!--<span style="color: rgb(255, 0, 0);"></span>-->
                            <!--</p>-->
                        <!--</li>-->
                        <!--<li class="ctrl" style="margin-top: 30px;">-->
                            <!--<input id="loginBtn" type="submit" value="登录" @click="submitLogin('loginForm')" class="loginbtn">-->
                            <!--<label>-->
                                <!--<input id="remember" type="checkbox" checked="checked">记住密码-->
                            <!--</label>-->
                            <!--<a href="javascript:alert('请联系维护人员');">忘记密码？</a>-->
                        <!--</li>-->
                    <!--</ul>-->
                <!--</form>-->
                <el-form :model="loginForm" :rules="loginRules" ref="loginForm" class="loginbox">
                    <ul>
                        <li >
                            <el-form-item prop="name">
                                <input v-model="loginForm.name" type="text" class="loginuser">
                            </el-form-item>
                        </li>
                        <li >
                            <el-form-item prop="password">
                                <input type="password" v-model="loginForm.password"  class="loginpwd">
                            </el-form-item>
                        </li>
                        <li style="margin-bottom: -20px;" >
                            <el-form-item prop="code">
                                <!--<el-col :span="6">-->
                                    <!--<el-input  @keyup.enter.native ="submitLogin('loginForm')"  v-model="loginForm.code" maxlength="4" type="text" class="logincode"  ></el-input>-->
                                <!--</el-col>-->
                                    <input @keyup.enter.native ="submitLogin('loginForm')"  v-model="loginForm.code" maxlength="4" type="text" class="logincode">
                                <s-identify class="identify"  :identifyCode="identifyCode"></s-identify>
                                <!--<img src="/api/getValidateCode.do"  class="valid-pic">-->
                                <span @click="refreshCode" class="refreshCode" >点击刷新验证码</span>
                            </el-form-item>
                        </li>
                        <li >
                            <!--<el-form-item>-->
                                <!--<el-button type="primary" @click="submitLogin('loginForm')">提交</el-button>-->
                            <!--</el-form-item>-->
                            <input id="loginBtn" type="submit" value="登录" @click="submitLogin('loginForm')" class="loginbtn">
                            <label>
                            <input id="remember" type="checkbox" checked="checked">记住密码
                            </label>
                            <a href="javascript:alert('请联系维护人员');">忘记密码？</a>
                        </li>
                    </ul>
                </el-form>

            </div>
        </div>
    </div>
</template>

<style>




    body{
        padding: 0;
        margin: 0;
    }
    body, html {
        font-size: 12px;
        font-family: 'Microsoft YaHei',Arial,'Times New Roman',SimHei,Helvetica,sans-serif;
    }
    p {
        display: block;
        -webkit-margin-before: 1em;
        -webkit-margin-after: 1em;
        -webkit-margin-start: 0px;
        -webkit-margin-end: 0px;
    }
    li, ol, ul {
        list-style: none;
    }
    a {
        text-decoration: none;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        outline: 0;
    }
    a:hover{
        text-decoration: none;
    }
    input, select {
        vertical-align: sub;
        font-size: 12px;
        color: #fff;
        border: none;
    }


    .login{
        background-color: #1c77ac;
        background-image: url(/chat-backend/imgs/login/light.png);
        background-repeat: no-repeat;
        background-position: center top;
        overflow: hidden;
        position: absolute;
        width: 100%;
        height: 100%;
    }
    .mainBody{
        width: 100%;
        height: 100%;
        position: absolute;
        z-index: -1;
    }
    .nprogress-container{
        position:fixed !important;
        width:100%;
        height:50px;
        z-index:2048;
        pointer-events:none;
    }
    .cloud{
        position:absolute;
        top:0;
        left:0;
        width:100%;
        height: 100%;
        background: url(/chat-backend/imgs/login/cloud.png) no-repeat;
        z-index:1;
        opacity:.5;
    }
    .logintop {
        height: 47px;
        position: absolute;
        top: 0;
        background: url(/chat-backend/imgs/login/loginbg1.png) repeat-x;
        z-index: 100;
        width: 100%;
    }
    .logintop span {
        color: #fff;
        line-height: 47px;
        background: url(/chat-backend/imgs/login/loginsj.png) no-repeat 21px 18px;
        text-indent: 44px;
        color: #afc5d2;
        float: left;
    }
    .loginbody {
        background: url(/chat-backend/imgs/login/loginbg3.png) no-repeat center center;
        width: 100%;
        height: 100%;
        overflow: hidden;
        position: absolute;
        top: 47px;
    }
    .loginbox {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 692px;
        height: 336px;
        margin: -193px 0 0 -346px;
        background: url(/chat-backend/imgs/login/logininfo.png) no-repeat;
    }
    .loginbox ul {
        margin-top: 88px;
        margin-left: 285px;
        padding: 0;
    }
    .loginbox li {
        margin-bottom: 16px;
    }
    .loginbox .loginuser {
        width: 343px;
        height: 40px;
        background: url(/chat-backend/imgs/login/loginuser_343x40.png) no-repeat;
        border: none;
        line-height: 40px;
        padding-left: 44px;
        font-size: 14px;
        font-weight: 700;
    }
    .loginbox .loginpwd {
        width: 343px;
        height: 40px;
        background: url(/chat-backend/imgs/login/loginpassword_343x40.png) no-repeat;
        border: none;
        line-height: 40px;
        padding-left: 44px;
        font-size: 14px;
    }
    .loginbox input {
        color: #90a2bc;
    }
    .loginbox .logincode {
        width: 143px;
        height: 40px;
        background: url(/chat-backend/imgs/login/logincode.png) no-repeat;
        border: none;
        line-height: 40px;
        padding-left: 44px;
        font-size: 18px;
        font-weight: 700;
        float: left;
    }
    .loginbox .valid-pic {
        display: inline-block;
        width: 70px;
        height: 30px;
        line-height: 30px;
        text-align: center;
        font-size: 18px;
        letter-spacing: 1px;
        margin-left: 8px;
        vertical-align: bottom;
        border: 1px #90a2bc solid;
        cursor: pointer;
        border-radius: 2px;
    }
    .loginbox img {
        display: inline;
    }
    .loginbox .loginbtn {
        width: 111px;
        height: 35px;
        background: url(/chat-backend/imgs/login/buttonbg.png) repeat-x;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        line-height: 34px;
        border: none;
    }
    .loginbox .ctrl label {
        cursor: pointer;
        color: #687f92;
        margin-left: 25px;
    }
    .loginbox .ctrl label input {
        margin-right: 2px;
        display: inline;
        margin:0;
    }
    .loginbox .ctrl a {
        color: #687f92;
    }
    .loginbox a {
        display: inline;
        margin-left: 25px;
        text-decoration: none;
    }

    #s-canvas{
        width: 80px;
    }
    .identify{
        width: 80px;
        float: left;
        margin-left: 4px;
    }
    .refreshCode{
        cursor: pointer;
        line-height:40px;
        margin-left:4px;
    }


</style>

<script>
    /** 白云飘飘 **/
    var x = 100;
    var y = -150;
    setInterval(function (){
        if(x>=2000){
            x = -500;
        }
        $('.cloud:first').css('background-position', x+'px  100px');
        x =x+2.8;
    },100);
    setInterval(function (){
        if(y>=2000){
            y = -500;
        }
        $('.cloud:last').css('background-position', y+'px  -50px');
        y = y+2.8;
    },100);
    /** 白云飘飘 end **/




    export default {
        data() {
            let validCode=(rule, value,callback)=>{
                    if (!value){
                        callback(new Error('请输入验证码'))
                    }else  if (value!==this.identifyCode){
                        callback(new Error('验证码输入错误'))
                    }else {
                        callback()
                    }
                };
            return {
                identifyCodes: "1234567890",
                identifyCode: "",
                loading: false,
                loginForm: {
                    name:'',
                    password:'',
                    code:'',
                },
                loginRules: {
                    name: [
                        {required: true, message: '请输入用户名', trigger: 'blur'},
                    ],
                    password: [
                        {required: true, message: '请输入密码', trigger: 'blur'},
                        {min:6, max: 10, message: '密码长度应该在6-10个之间', trigger: 'blur'}
                    ],
                    code:[
                        {required: true, validator: validCode, trigger: 'blur'},
                    ]
                }
            };
        },
        methods: {
            submitLogin(loginForm) {
                let _this = this;
                let _duration = 1000;
                this.$refs[loginForm].validate((valid) => {
                    if(valid) {
                        _this.loading = true;
                        window.axios.post('/auth/login', _this.loginForm).then(function (response) {
                            let data = response.data;
                            if (data.status === 0) {

                                sessionStorage.setItem('src-admin', JSON.stringify(data.user));
                                setTimeout(function () {
                                  _this.$router.push({path: '/user'});
                                }, _duration);
                            } else {
                                alert(data.message);
                                _this.loading = false;
                            }
                        }).catch(function (error) {
                            _this.loading = false;
                            console.log(error);
                        });
                    }
                });
            },
            randomNum(min, max) {
                return Math.floor(Math.random() * (max - min) + min);
            },
            refreshCode() {
                this.identifyCode = "";
                this.makeCode(this.identifyCodes, 4);
            },
            makeCode(o, l) {
                for (let i = 0; i < l; i++) {
                    this.identifyCode += this.identifyCodes[
                        this.randomNum(0, this.identifyCodes.length)
                        ];
                }
            }
        },
        mounted() {
            this.identifyCode = "";
            this.makeCode(this.identifyCodes, 4);
        }
    }

</script>
