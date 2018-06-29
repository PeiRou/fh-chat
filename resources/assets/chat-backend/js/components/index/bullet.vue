<template>
    <div id="mainContent" style="height: 700px; overflow-y: auto; overflow-x: visible;">
        <div class="route-animated" >
            <div class="query-form">
                <el-button type="success" size="mini" @click="showDialog" >添加公告</el-button>
            </div>
            <el-table
                    :data="tableData"
                    border
                    v-loading="listLoading"
                    style="width: 100%">
                <el-table-column
                        prop="content"
                        label="公告内容"
                >
                </el-table-column>
                <el-table-column
                        prop="type"
                        label="房间"
                >
                </el-table-column>
                <el-table-column
                        prop="created_at"
                        label="添加时间"
                >
                </el-table-column>
                <el-table-column
                        prop="created_hand"
                        label="添加人"
                >
                </el-table-column>
                <el-table-column
                        prop="updated_at"
                        label="修改时间"
                >
                </el-table-column>
                <el-table-column
                        prop="updated_hand"
                        label="修改人"
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
                                @click="handleDelete(scope.$index, scope.row)">删除
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
                    :total="total"
            >
            </el-pagination>
        </div>
        <!--edit Form dialog-->
        <el-dialog :title="title" :visible.sync="editDialogVisible"  width="25%"  v-loading="formLoading" >
            <el-form :model="form"  :rules="form" ref="form" :label-position="labelPosition" label-width="80px" >
                <input type="hidden"    v-model="form.id" >
                <el-form-item label="公告内容" prop="content" >
                    <el-input  type="textarea" auto-complete="off"   v-model="form.content" placeholder="请输入内容"></el-input>
                </el-form-item>
                <el-form-item label="房间" prop="type" >
                    <el-select v-model="form.type" >
                        <el-option label="通用" value="通用"></el-option>
                        <el-option label="爱彩聊天室" value="爱彩聊天室"></el-option>
                    </el-select>
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
                formLoading: false,
                editDialogVisible:false,
                title:'',
                labelPosition:'right',
                form:{
                    id:'',
                    content:'',
                    type:'',
                },
                tableData: [],
                rules:{
                    content:[
                        {required: true, message: '请输入公告内容', trigger: 'blur'}
                    ],
                    type:[
                        {required: true, message: '请选择房间类型', trigger: 'blur'}
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
                window.axios.get('/bullet', { params: query }).then(function (response) {
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
                _this.title = '添加公告';
                _this.editDialogVisible = true;

            },
            submit:function(form){
                let _this     = this ,
                    _duration = 1500;
                _this.$refs[form].validate((valid) => {
                    if(valid) {
                        _this.formLoading = true;
                        if(_this.form.id==''){
                            window.axios.post('/bullet', _this.form).then(function (response) {
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
                            window.axios.put('/bullet/'+_this.form.id, _this.form).then(function (response) {
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
                _this.form.content = row.content;
                _this.form.type = row.type;
                _this.form.id = row.id;
                _this.title = '编辑公告';
            },
            handleDelete(index, row) {
                let _this = this, param = {id:row.id};
                _this.$confirm('确定要删除这条数据？', '提示', {type: 'warning'}).then( () => {
                    _this.listLoading = true;
                    window.axios.delete('/bullet/destroy', { data: param}).then(function (response) {
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
            initForm:function () {
                this.form.id        = '';
                this.form.content   = '';
                this.form.type      = '';
            }
        },
        mounted() {
            this.getData();
        }
    }
</script>