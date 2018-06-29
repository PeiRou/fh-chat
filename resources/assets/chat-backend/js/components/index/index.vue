<template>
    <div>
        <!--header box-->
        <div class="header">
            <div class="wrap clearfix">
                <div class="logo fl"><h1 style="font-size: 30px; color: rgb(255, 255, 255); padding-top: 4px ">聊天室后台</h1></div>
                <div class="userinfo fr">
                    <i class="icon-head"></i>
                    <span>{{userName}}</span>
                    <span style="font-size: 16px; margin-left: 0px; cursor: pointer;" @click="settingFormVisible = true">修改密码</span>
                    <a href="javascript:void(0)" v-on:click="logout">
                        <img src="/chat-backend/imgs/index/btn_lgout.png" alt="">
                    </a>
                </div>
            </div>
        </div>
        <!--header box end-->

        <div class="main clearfix">
            <!--leftmenu -->
            <div class="leftmenu fl">
                <div class="tit"><i class="icon-drop"></i>菜单</div>
                <div id="menu" class="menu">
                    <div v-bind:class="classItem(item)" v-for="(item, index) in $router.options.routes" :key="index"  v-if="!item.hidden" @click="changeRoute(item.children[0].path)">
                        <i class="icon-book"></i>{{ item.children[0].name  }}
                    </div>
                    <!---->
                </div>
            </div>
            <!--leftmenu end-->

            <!--rightbox -->
            <div class="rightbox fl">
                <div class="agent-tit">
                    位置：<span id="pagePos">{{currentPathName}}</span> <!---->
                </div>
                <router-view></router-view>
            </div>
            <!--rightbox end-->
        </div>

        <!--settingForm dialog-->
        <el-dialog title="修改密码" :visible.sync="settingFormVisible"  width="30%" >
            <el-form :model="settingForm"  :rules="rules" ref="settingForm" >
                <el-form-item label="账号"  :label-width="formLabelWidth">
                    <el-input  auto-complete="off" readonly v-model="userName"></el-input>
                </el-form-item>
                <el-form-item label="旧密码" prop="passed" :label-width="formLabelWidth">
                    <el-input  type="password" auto-complete="off" v-model="settingForm.passed"></el-input>
                </el-form-item>
                <el-form-item label="新密码" prop="pass" :label-width="formLabelWidth">
                    <el-input  type="password" auto-complete="off" v-model="settingForm.pass" ></el-input>
                </el-form-item>
            </el-form>
            <div slot="footer" class="dialog-footer" style="margin-left: 115px; text-align: left;">
                <el-button type="info" @click="submitSetting('settingForm')">提 交</el-button>
                <el-button @click="settingFormVisible = false">取 消</el-button>
            </div >
        </el-dialog>
        <!--settingForm dialog-->

    </div>

</template>

<style>
    body {
        min-width: 1080px;
        overflow-y: hidden;
        margin:0;
        padding:0;
        background: white;
    }
    h1, h2, h3, h4, h5, h6 {
        font-size: 100%;
    }
    div {
        display: block;
    }
    h1 {
        display: block;
        font-size: 2em;
        -webkit-margin-before: 0.67em;
        -webkit-margin-after: 0.67em;
        -webkit-margin-start: 0px;
        -webkit-margin-end: 0px;
        font-weight: bold;
    }
    a {
        text-decoration: none;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        outline: 0;
    }
    i {
        display: inline-block;
    }
    fieldset, img {
        border: 0;
        display: block;
    }
    .fl {
        float: left;
    }
    .fr {
        float: right;
    }

    .header{
        height: 88px;
        background: url(/chat-backend/imgs/index/topbg.gif) repeat-x;
    }
    .header .wrap {
        background: url(/chat-backend/imgs/index/topleft.jpg) no-repeat left center;
        height: 100%;
    }
    .clearfix {
        zoom: 1;
    }
    .header .logo {
        margin: 9px 0 0 15px;
    }
    .header .userinfo {
        line-height: 40px;
        margin: 20px 20px 0 0;
    }
    .header .userinfo i {
        vertical-align: bottom;
    }
    .icon-head {
        width: 26px;
        height: 26px;
        background: url(/chat-backend/imgs/index/icon_head.png) no-repeat center;
    }
    .header .userinfo span {
        display: inline-block;
        font-size: 18px;
        text-decoration: underline;
        color: #c2d6df;
        margin: 0 10px;
        line-height: 25px;
        vertical-align: bottom;
    }
    .header .userinfo a {
        display: inline-block;
        vertical-align: bottom;
    }
    .main .leftmenu {
        width: 150px;
        font-size: 14px;
        color: #fff;
    }
    .main .leftmenu .tit {
        height: 40px;
        line-height: 40px;
        background: url(/chat-backend/imgs/index/lefttop.gif) repeat-x;
        position: relative;
    }
    .main .leftmenu i {
        margin: 10px 8px 0 10px;
        float: left;
    }
    .icon-drop {
        width: 20px;
        height: 21px;
        background: url(/chat-backend/imgs/index/icon_drop.png) no-repeat center;
    }
    .main .leftmenu .item {
        background: #d4e7f0;
        height: 35px;
        border-right: solid 1px #b7d5df;
        border-bottom: solid 1px #b9dac3;
        line-height: 35px;
        font-weight: 700;
        color: #000;
        font-size: 12px;
        cursor: pointer;
    }
    .main .leftmenu .item:hover {
        background: #b9dac3;
        text-decoration: underline;
    }
    .main .leftmenu i {
        margin: 10px 8px 0 10px;
        float: left;
    }
    .main .leftmenu .active {
        background: #b9dac3;
    }
    .main .leftmenu .active:hover {
        text-decoration: none;
    }
    .icon-book {
        width: 16px;
        height: 16px;
        background: url(/chat-backend/imgs/index/icon_book.png) no-repeat center;
    }
    .main .rightbox {
        width: calc(100% - 150px);
    }
    .main .rightbox .agent-tit {
        font-weight: 700;
        background: #edf6fa;
        padding-left: 12px;
        height: 40px;
        line-height: 39px;
        border-bottom: solid 1px #b7d5df;
        color:black;
    }
    .main .rightbox .agent-tit span {
        font-weight: 400;
    }
</style>



<script type="text/ecmascript-6">
    export default{
        data() {
            return {
                currentPath: '/',
                currentPathName: '首页',
                userName: '',
                settingFormVisible:false,
                formLabelWidth: '120px',
                settingForm:{
                    name:JSON.parse(sessionStorage.getItem('src-admin')).name,
                    passed:'',
                    pass:'',
                },
                rules:{
                    passed:[
                        {required: true, message: '请输入旧密码', trigger: 'blur'}
                    ],
                    pass:[
                        {required: true, message: '请输入新密码', trigger: 'blur'}
                    ],

                },
            }
        },
        watch: {
            '$route'(to, from) {
                this.currentPath = to.path;
                this.currentPathName = to.name;
            }
        },

        methods: {
            logout() {
                let _this     = this;
                let _duration = 1000;
                _this.$confirm('是否确认退出？', '提示', {type: 'warning'}).then( () => {
                    window.axios.post('/auth/logout').then( function (response) {
                        let data = response.data;
                        if(!data.status) {
                            sessionStorage.removeItem('src-admin');
                            _this.$message({
                                message: data.message,
                                type: 'success',
                                duration: _duration
                            });
                            setTimeout(function () {
                                _this.$router.push({path: '/login'});
                            }, _duration);
                        }
                    }).catch(function (error) {
                        console.log(error);
                        _this.$message.error("退出失败");
                    })
                }).catch(() => {
                    console.log('cancel')
                })
            },
            changeRoute(path){
                this.$router.push({path: path});
            },
            submitSetting(formName){
                let _this = this;
                let _duration = 1000;
                _this.$refs[formName].validate((valid) => {
                    if(valid) {
                        window.axios.post('/auth/setting', _this.settingForm).then(function (response) {
                            let data = response.data;
                            if(data.status === 0) {
                                _this.settingFormVisible=false;
                                _this.$message({
                                    'message': data.message,
                                    'type': 'success',
                                    'duration': _duration
                                });
                                setTimeout(function () {
                                    sessionStorage.removeItem('src-admin');
                                    _this.$router.push({path: '/login'});
                                }, _duration);
                            }else {
                                _this.$message.error(data.message);
                            }
                        }).catch(function (error) {
                            console.log(error);
                        })
                    }else {
                        console.log('Valid Error.');
                        return false;
                    }
                });
            },
            classItem: function (i) {
                return i.children[0].path === this.$route.path ? ' item active ' : 'item';
            }
        },
        mounted() {
            this.currentPath = this.$route.path;
            this.currentPathName = this.$route.name;
            let user = sessionStorage.getItem('src-admin');
            if(user) {
                this.userName = JSON.parse(user).name;
            }
        }
    }
</script>