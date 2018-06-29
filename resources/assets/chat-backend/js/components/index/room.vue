<template>
    <div id="mainContent" style="height: 700px; overflow-y: auto; overflow-x: visible;">
        <div class="route-animated" >
            <div class="query-form">
                <el-button type="success" size="mini" plain disabled  @click="showDialog" >添加房间</el-button>
                <!--disabled-->
            </div>
            <el-table
                    :data="tableData"
                    border
                    v-loading="listLoading"
                    style="width: 100%">
                <el-table-column
                        prop="name"
                        label="房间名称"
                        >
                </el-table-column>
                <el-table-column
                        prop="type"
                        label="房间类型"
                        >
                    <template slot-scope="scope">
                        <span style="color: #0000F0">{{ scope.row.type }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="online"
                        label="在线人数"
                        >
                </el-table-column>
                <el-table-column
                        prop="is_disable"
                        label="是否禁言"
                        >
                    <template slot-scope="scope"  >
                       {{ scope.row.is_disable=='false'?'禁言':'正常' }}
                    </template>
                </el-table-column>
                <el-table-column
                        prop="condition"
                        label="发言条件（最近2天）"
                        >
                    <template slot-scope="scope">
                        <span >充值不少于{{ scope.row.recharge }} ,打码不少于{{scope.row.chip }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="created_at"
                        label="创建时间"
                        >
                </el-table-column>

                <el-table-column label="操作">
                    <template slot-scope="scope">
                        <el-button
                            size="mini"
                            @click="handleEdit(scope.$index, scope.row)">修改
                        </el-button>
                        <el-button
                            size="mini"
                            disabled
                            @click="handleDelete(scope.$index, scope.row)">删除
                        </el-button>

                        <el-button
                            size="mini"
                            @click="handleDisable(scope.$index, scope.row)">禁言
                        </el-button>
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
                    >
            </el-pagination>
        </div>
        <!--edit Form dialog-->
        <el-dialog :title="title" :visible.sync="editDialogVisible"  width="25%" v-loading="formLoading" >
            <el-form :model="form"  :rules="rules" ref="form" :label-position="labelPosition" label-width="80px" >
                <input type="hidden" v-model="form.id">
                <el-form-item label="房间名称" prop="name" >
                    <el-input  auto-complete="off"   v-model="form.name"></el-input>
                </el-form-item>
                <el-form-item label="充值要求" prop="recharge" >
                    <el-input  type="number" min="0" auto-complete="off" v-model="form.recharge"></el-input>
                </el-form-item>
                <el-form-item label="打码要求" prop="chip" >
                     <el-input  type="number" min="0" auto-complete="off" v-model="form.chip" ></el-input>
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
                listLoading: false,
                formLoading: false,
                editDialogVisible:false,
                title:'',
                labelPosition:'right',
                form:{
                    id:'',
                    name:'',
                    recharge:'',
                    chip:'',
                },
                tableData: [],
                rules:{
                    name:[
                        {required: true, message: '请输入房间名称', trigger: 'blur'}
                    ],
                    recharge:[
                        {required: true, message: '请输入充值要求', trigger: 'blur'}
                    ],
                    chip:[
                        {required: true, message: '请输入打码要求', trigger: 'blur'}
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
                window.axios.get('/room', { params: query }).then(function (response) {
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
                _this.title = '添加房间';
                _this.editDialogVisible = true;
            },
            submit:function(form){
                let _this     = this ,
                    _duration = 1500;
                _this.$refs[form].validate((valid) => {
                    if(valid) {
                        _this.formLoading = true;
                        if(_this.form.id==''){
                            window.axios.post('/room', _this.form).then(function (response) {
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
                            window.axios.put('/room/'+_this.form.id, _this.form).then(function (response) {
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
                _this.editDialogVisible = true;
                _this.title = '修改房间';
                _this.form.id = row.id;
                _this.form.name = row.name;
                _this.form.recharge = row.recharge;
                _this.form.chip = row.chip;
            },
            handleDelete(index, row) {
                let _this = this, param = {id:row.id};
                _this.$confirm('确定要删除这条数据？', '提示', {type: 'warning'}).then( () => {
                    _this.listLoading = true;
                    window.axios.delete('/room/destroy', { data: param}).then(function (response) {
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
            handleDisable(index, row) {
                let _this = this;
                _this.$confirm('确定要对房间进行禁言吗？', '提示', {type: 'warning'}).then( () => {
                    _this.listLoading = true;
                    window.axios.post('/room/disable/'+row.id).then(function (response) {
                        let res = response.data;
                        _this.$message({
                            message: res.message,
                            type: res.status === 0 ? 'success' : 'error'
                        });
                        _this.listLoading = false;
                        _this.getData();
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
            initForm:function () {
                this.form.id       = '';
                this.form.name     = '';
                this.form.chip     = '';
                this.form.recharge = '';
            }
        },
        mounted() {
            this.getData();
        }
    }
</script>