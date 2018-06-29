<template>
    <div id="mainContent" style="height: 700px; overflow-y: auto; overflow-x: visible;">
        <div class="route-animated" >
            <div class="query-form">
                <el-button type="success" size="mini" @click="showDialog" >添加角色</el-button>
            </div>
            <el-table
                    :data="tableData"
                    border
                    v-loading="listLoading"
                    style="width: 100%">
                <el-table-column
                        prop="name"
                        label="角色名"
                >
                </el-table-column>
                <el-table-column
                        prop="bg_color"
                        label="聊天信息效果"
                >
                    <template slot-scope="scope">
                        <div v-bind:style="setBackground(scope.row.bg_color1,scope.row.bg_color2,scope.row.font_color)">聊天信息效果</div>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="length"
                        label="消息最大长度"
                >
                </el-table-column>
                <el-table-column
                        prop="permission"
                        label="权限"
                >
                    <template slot-scope="scope">
                        <el-tag type="success" v-for="(permi,index) in scope.row.permission.split(',')" :key="index" v-if="permi=='发言' || permi=='发图' " >{{permi}}</el-tag>
                        <el-tag type="warning" v-for="(permi,index) in scope.row.permission.split(',')" :key="index" v-if="permi=='踢人' || permi=='禁言'" >{{permi}}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="description"
                        label="描述"
                >
                </el-table-column>

                <el-table-column label="操作">
                    <template slot-scope="scope">
                        <el-button size="mini" @click="handleEdit(scope.$index, scope.row)">修改</el-button>
                        <el-button size="mini" v-if="scope.row.name==='游客' || scope.row.name==='默认层'" disabled @click="handleDelete(scope.$index, scope.row)">删除</el-button>
                        <el-button size="mini" v-else @click="handleDelete(scope.$index, scope.row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </div>
        <div class="block" style="margin-top: 20px; text-align: center">
            <el-pagination
                    background
                    @size-change="handleSizeChange"
                    @current-change="handleCurrentChange"
                    :current-page="currentPage"
                    :page-sizes="[10, 20, 50, 100,200]"
                    :page-size="pageSize"
                    layout="total, sizes, prev, pager, next"
                    :total="total"
            >
            </el-pagination>
        </div>
        <!--edit Form dialog-->
        <el-dialog :title="title" :visible.sync="editDialogVisible"  width="25%"  v-loading="formLoading">
            <el-form :model="form"  :rules="rules" ref="form" label-width="110px" >
                <el-form-item label="角色名" prop="name" >
                    <el-input  auto-complete="off"   v-model="form.name"></el-input>
                </el-form-item>
                <el-form-item label="角色类型" prop="type" >
                    <el-select  placeholder="请选择" v-model="form.type">
                        <el-option label="游客" value="游客"></el-option>
                        <el-option label="会员" value="会员"></el-option>
                        <el-option label="管理员" value="管理员"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="会员等级" prop="level" v-if="form.type==='会员'" >
                    <el-select  placeholder="请选择会员等级" v-model="form.level">
                        <el-option label="普通会员" value="普通会员"></el-option>
                        <el-option label="白银会员" value="白银会员"></el-option>
                        <el-option label="黄金会员" value="黄金会员"></el-option>
                        <el-option label="铂金会员" value="铂金会员"></el-option>
                        <el-option label="钻石会员" value="钻石会员"></el-option>
                        <el-option label="至尊会员" value="至尊会员"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="聊天背景颜色" >
                    <el-color-picker v-model="form.bg_color1"></el-color-picker>
                    <el-color-picker v-model="form.bg_color2"></el-color-picker>
                </el-form-item>
                <el-form-item label="字体" prop="font_color" >
                    <el-color-picker v-model="form.font_color"></el-color-picker>
                </el-form-item>
                <el-form-item clearable label="消息最大长度" prop="length" >
                    <el-select  placeholder="请选择" v-model="form.length">
                        <el-option label="不限制" value="不限制"></el-option>
                        <el-option label="100" value="100"></el-option>
                        <el-option label="200" value="200"></el-option>
                        <el-option label="300" value="300"></el-option>
                        <el-option label="500" value="500"></el-option>
                        <el-option label="1000" value="1000"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item  label="角色权限" >
                    <el-checkbox-group v-model="form.permission">
                        <el-checkbox label="踢人"></el-checkbox>
                        <el-checkbox label="禁言"></el-checkbox>
                        <el-checkbox label="发言"></el-checkbox>
                        <el-checkbox label="发图"></el-checkbox>
                    </el-checkbox-group>
                </el-form-item>
                <el-form-item  label="描述"  >
                    <el-input  type="textarea" autosize auto-complete="off" v-model="form.description"  ></el-input>
                </el-form-item>
            </el-form>
            <div slot="footer" class="dialog-footer" style="margin-left: 115px; text-align: left;">
                <el-button type="info" @click="submit('form')">提 交</el-button>
                <el-button @click="editDialogVisible = false">取 消</el-button>
            </div >
        </el-dialog>
        <!--edit Form dialog-->
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
    .el-table__body-wrapper {
        overflow: hidden !important;
        position: relative;
    }

</style>
<script >
    export default{
        data() {
            return {
                currentPage: 1,
                pageSize: 20,
                total:0,
                listLoading: false,
                editDialogVisible:false,
                formLoading: false,
                title:'',
                form:{
                    id:'',
                    name:'',
                    type:'会员',
                    level:'',
                    bg_color1:'#199ED8',
                    bg_color2:'#199ED8',
                    font_color:'#ffffff',
                    length:'100',
                    permission:[],
                    description:'',
                },
                tableData: [],
                rules:{
                    name:[
                        {required: true, message: '请输入角色名', trigger: 'blur'}
                    ],
                    font_color:[
                        {required: true, message: '请选择字体颜色', trigger: 'blur'}
                    ],
                    length:[
                        {required: true, message: '请选择消息最大长度', trigger: 'blur'}
                    ]
                },
            }
        },
        methods: {
            getData:function () {
                let _this = this,
                    query = {
                        rows: _this.pageSize,
                        page: _this.currentPage,
                    };
                _this.listLoading = true;
                window.axios.get('/role', { params: query }).then(function (response) {
                    let res = response.data;
                    if (res.status === 0) {
                        let data        = res.data;
                        _this.tableData = data.data;
                        _this.total     = data.total;
                    } else {
                        _this.$message({
                            message: '数据获取失败',
                            type: 'error',
                            duration: 3 * 1000
                        });
                    }
                    _this.listLoading = false;
                }).catch(function (error) {
                    _this.listLoading = false;
                    console.log(error);
                });
            },
            showDialog:function(){
                let _this = this;
                _this.initForm();
                _this.title             = '添加角色';
                _this.editDialogVisible = true;
            },
            submit:function(form){
                let _this     = this ,
                    _duration = 1500;
                _this.$refs[form].validate((valid) => {
                    if(valid) {
                        _this.formLoading = true;
                        if(_this.form.id==''){
                            window.axios.post('/role', _this.form).then(function (response) {
                                let res = response.data;
                                _this.formLoading       = false;
                                _this.editDialogVisible = false;
                                _this.$message({
                                    message: res.message,
                                    type: res.status === 0 ? 'success' : 'error',
                                    duration: _duration
                                });
                                _this.getData();
                            }).catch(function (error) {
                                _this.formLoading = false;
                                console.log(error);
                            });
                        }else{
                            window.axios.put('/role/'+_this.form.id, _this.form).then(function (response) {
                                let res = response.data;
                                _this.editDialogVisible = false;
                                _this.formLoading = false;
                                _this.$message({
                                    message: res.message,
                                    type: res.status === 0 ? 'success' : 'error',
                                    duration: _duration
                                });
                                _this.getData();
                            }).catch(function (error) {
                                _this.formLoading = false;
                                console.log(error);
                            });
                        }
                    }
                });
            },
            handleEdit(index, row) {
                let _this= this;
                _this.title = '修改角色';
                _this.form.id           = row.id;
                _this.form.name         = row.name;
                _this.form.type         = row.type;
                _this.form.level        = row.level;
                _this.form.bg_color1    = row.bg_color1;
                _this.form.bg_color2    = row.bg_color2;
                _this.form.font_color   = row.font_color;
                _this.form.length       = row.length;
                _this.form.permission   = row.permission.split(",");
                _this.form.description  = row.description;
                _this.editDialogVisible = true;

            },
            handleDelete(index, row) {
                let _this = this, param = {id:row.id};
                _this.$confirm('确定要删除这条数据？', '提示', {type: 'warning'}).then( () => {
                    _this.listLoading = true;
                    window.axios.delete('/role/destroy', { data: param}).then(function (response) {
                        let res = response.data;
                        if(res.status === 0 ) {
                            _this.util.removeByValue(_this.tableData, row.id);
                            _this.total = _this.total-1;
                        }
                        _this.$message({
                            message: res.message,
                            type: res.status === 0 ? 'success' : 'error'
                        });
                        _this.listLoading = false;
                    }).catch(function (error) {
                        _this.listLoading = false;
                        console.log(error);
                    });
                }).catch(() => {
                    _this.listLoading = false;
                });
            },
            handleSizeChange(val) {
                this.pageSize = val;
                this.getData();
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.getData();
            },
            initForm:function(){
                let _this = this;
                _this.form.id           = '';
                _this.form.name         = '';
                _this.form.type         = '会员';
                _this.form.level        = '';
                _this.form.bg_color1    = '#199ED8';
                _this.form.bg_color2    = '#199ED8';
                _this.form.font_color   = '#ffffff';
                _this.form.length       = '100';
                _this.form.permission   = [];
                _this.form.description  = '';
            },
            setBackground(bg_1,bg_2,f_color){
                return  'border-radius: 3px; font-size: 12px; padding: 5px; color:'+f_color+'; ' +
                        'background: -webkit-linear-gradient(left, '+bg_1+','+bg_2+'); ' +
                        'background: -o-linear-gradient(right, '+bg_1+','+bg_2+');  '+
                        'background: -moz-linear-gradient(right, '+bg_1+','+bg_2+'); '+
                        'background: linear-gradient(to right, '+bg_1+','+bg_2+')';
            },
        },
        computed: {

        },
        mounted() {
            this.getData();
        }
    }
</script>