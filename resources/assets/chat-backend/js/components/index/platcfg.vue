<template>
    <div id="mainContent" style="height: 700px; overflow-y: auto; overflow-x: visible;">
        <div class="route-animated" >
            <div class="query-form">
                是否开启聊天室：    <el-switch
                            active-color="#13ce66"
                            inactive-color="#ff4949"
                            v-model="form.is_open"
                            >
                    </el-switch>
            </div>
            <div style="width:30%;margin-left: 100px;margin-top:50px;">
                <el-form label-width="125px" class="demo-ruleForm"  :model="form"  :rules="rules" ref="form" >
                    <el-form-item label="计划发布方式" prop='type'>
                        <el-select v-model="form.schedule_type"  >
                            <el-option label="手动发布" value="手动发布"></el-option>
                            <el-option label="软件发布" value="软件发布"></el-option>
                        </el-select>
                    </el-form-item>
                        <el-form-item label="计划推送游戏">
                            <el-checkbox-group  v-model="form.schedule_games" >
                                <div style="display: inline-flex;">
                                    <el-checkbox label="北京赛车"></el-checkbox>
                                    <el-checkbox label="秒速赛车"></el-checkbox>
                                    <el-checkbox label="幸运飞艇"></el-checkbox>
                                    <el-checkbox label="重庆时时彩"></el-checkbox>
                                    <el-checkbox label="PC蛋蛋"></el-checkbox>
                                </div>
                            </el-checkbox-group>
                        </el-form-item>
                    <el-form-item label="计划底部信息" >
                        <el-input
                                type="textarea"
                                autosize
                                placeholder="请输入内容"
                                v-model="form.schedule_msg"
                                >
                        </el-input>
                    </el-form-item>
                    <div style="display: inline-flex;">
                        <div>
                            <el-form-item label="发言时段" prop="start_time" >
                                <el-time-select
                                    placeholder="起始时间"
                                    v-model="form.start_time"
                                    :picker-options="{
                                      start: '00:00',
                                      step: '00:15',
                                      end: '24:00'
                                }">
                                </el-time-select>
                            </el-form-item>
                        </div>
                        <div style="width:60px; line-height: 40px">&nbsp; ~ 次日</div>
                        <div style="margin-left: -125px">
                            <el-form-item prop="end_time" >
                                <el-time-select
                                    placeholder="结束时间"
                                    v-model="form.end_time"
                                    :picker-options="{
                                      start: '00:00',
                                      step: '00:15',
                                      end: '24:00',
                                }">
                                </el-time-select>
                            </el-form-item>
                        </div>
                    </div>

                    <el-form-item label="是否展开聊天室" prop="is_auto">
                        <el-select v-model="form.is_auto" >
                            <el-option label="自动展开(适用用呼量较少的平台)" value="1" ></el-option>
                            <el-option label="不自动展开" value="0" ></el-option>
                            <!--<el-option-->
                                    <!--v-for="item in form.options"-->
                                    <!--:key="item.value"-->
                                    <!--:label="item.label"-->
                                    <!--:value="item.value">-->
                            <!--</el-option>-->
                        </el-select>
                    </el-form-item>

                    <el-tooltip class="item" effect="light"  placement="right">
                        <div slot="content">如填写100,表示用户在平台下注金额≥100才会推送到聊天室<br/>如不推送任何下注信息请填0</div>
                        <el-form-item label="下注最低推送额" prop="min_money">
                            <el-input autocomplete="off" type="number" min="0" v-model="form.min_money"  ></el-input>
                        </el-form-item>
                    </el-tooltip>

                    <el-tooltip class="item" effect="light"  placement="right">
                        <div slot="content">黑名单内的ip无法注册和进入聊天室<br/>多个IP用|分隔，支持*通配符<br/>例如：218.124.2.88|218.124.5.*</div>
                        <el-form-item label="IP黑名单" >
                            <el-input autocomplete="off" v-model="form.ip_black"  ></el-input>
                        </el-form-item>
                    </el-tooltip>
                    <el-form-item>
                        <el-button type="info" @click="submit('form')">保存配置</el-button>
                    </el-form-item>
                </el-form>
            </div>
        </div>

    </div>

</template>

<style>
    .route-animated{
        -webkit-animation-duration: .3s;
        animation-duration: .3s;
        -webkit-animation-fill-mode: both;
        animation-fill-mode: both;
    }
    .query-form {
        padding: 10px 0 10px 25px ;
        background-color: #f2f2f2;
    }

</style>
<script >
    export default{
        data() {
            return {
                form:{
                    id:'',
                    is_open:'1',
                    schedule_type:'',
                    schedule_games:[],
                    schedule_msg:'',
                    start_time:'',
                    end_time:'',
                    is_auto:'',
                    min_money:'',
                    is_black:'',
                },
                rules:{
                    schedule_type:[
                        { required: true, message: '请选择计划发布方式', trigger: 'blur'}
                    ],
                    start_time:[
                        { required: true, message: '请输入发言起始时间', trigger: 'blur'}
                    ],
                    end_time:[
                        { required: true, message: '请输入发言结束时间', trigger: 'blur'}
                    ],
                    is_auto:[
                        {required: true, message: '请选择是否展开聊天室', trigger: 'blur'}
                    ],
                    min_money:[
                        {required: true, message: '请输入下注最低推送额', trigger: 'blur'}
                    ],
                },
            }
        },
        methods: {
            getData:function () {
                let _this = this;
                window.axios.get('/platcfg').then(function (response) {
                    let res = response.data;
                    if (res.status === 0) {
                        let data                    = res.data;
                        _this.form.id               = data.id;
                        _this.form.is_open          = data.is_open===1?true:false;
                        _this.form.schedule_type    = data.schedule_type;
                        _this.form.schedule_games   = data.schedule_games.split(",");
                        _this.form.schedule_msg     = data.schedule_msg;
                        _this.form.start_time       = data.start_time;
                        _this.form.end_time         = data.end_time;
                        _this.form.is_auto          = data.is_auto>0?'1':'0';
                        _this.form.min_money        = data.min_money;
                        _this.form.is_black         = data.is_black;
                    } else {
                        _this.$message({
                            message: '数据获取失败',
                            type: 'error',
                            duration: 3 * 1000
                        });
                    }
                }).catch(function (error) {
                    console.log(error);
                });
            },
            submit:function(form){
                let _this     = this ,
                    _duration = 1500;

                    console.log(_this.form);
                _this.$refs[form].validate((valid) => {
                    if(valid) {
                        window.axios.put('/platcfg/'+_this.form.id, _this.form).then(function (response) {
                            let res = response.data;
                            _this.$message({
                                message: res.message,
                                type: res.status === 0 ? 'success' : 'error',
                                duration: _duration
                            });
                            //_this.getData();
                        }).catch(function (error) {
                            console.log(error);
                        });
                    }
                });
            }
        },
        mounted() {
            this.getData();
        }
    }
</script>