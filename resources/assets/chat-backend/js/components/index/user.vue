<template>
    <div id="mainContent" style="height: 700px; overflow-y: auto; overflow-x: visible;">
        <div class="route-animated" >
            <div class="query-form">
                <el-form ref="searchForm" :inline="true"  :model="searchForm" size="mini" class="demo-form-inline">
                    <el-form-item  >
                        <el-input  placeholder="用户名/昵称" v-model="searchForm.username"></el-input>
                    </el-form-item>
                    <el-form-item label="角色">
                        <el-select  placeholder="所有会员" v-model="searchForm.chatRole" >
                            <el-option label="游客" value="游客" ></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="状态">
                        <el-select  placeholder="全部" v-model="searchForm.chatStatus">
                            <el-option label="正常" value="正常" ></el-option>
                            <el-option label="禁言" value="禁言" ></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item >
                        <el-input  placeholder="登录IP" v-model="searchForm.loginIp"></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="info" >查询</el-button>
                    </el-form-item>
                </el-form>
            </div>

            <el-table
                    :data="tableData"
                    border
                    style="width: 100%">
                <el-table-column
                        prop="id"
                        label="用户ID"
                        width="180">
                    <template slot-scope="scope">
                        <el-tag
                                type="info"
                                color="#e4ebf1"
                                close-transition>离线</el-tag>
                        {{scope.row.id}}
                    </template>
                </el-table-column>
                <el-table-column
                        prop="username"
                        label="用户名"
                        width="180">
                    <template slot-scope="scope">
                        <el-tag
                                type="info"
                                color="#e4ebf1"
                                close-transition>{{scope.row.name}}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="name"
                        label="昵称"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="login_ip"
                        label="IP"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="chatRole"
                        label="角色"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="chatStatus"
                        label="状态"
                        width="180">
                    <template slot-scope="scope">
                        <el-tag
                                type="success"
                                close-transition>{{scope.row.status}}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="recharge"
                        label="最近2天充值"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="recharge"
                        label="最近2天下注"
                        width="180">
                </el-table-column>

                <el-table-column label="操作">
                    <template slot-scope="scope">
                        <el-button
                                size="mini"
                                @click="handleEdit(scope.$index, scope.row)">修改
                        </el-button>
                        <el-button
                                size="mini"
                                @click="handleDisable(scope.$index, scope.row)">禁言
                        </el-button>
                        <el-button
                                size="mini"
                                @click="handleOut(scope.$index, scope.row)">踢出
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
                    :total="total">
            </el-pagination>
        </div>


        <!--edit Form dialog-->
        <el-dialog title="修改用户" :visible.sync="editDialogVisible"  :label-position="labelPosition"  width="25%" >
            <el-form :model="form"  :rules="rules" ref="form" :label-position="labelPosition" label-width="80px" >
                <el-form-item label="用户名" prop="username" >
                    <el-input  auto-complete="off"   v-model="form.username"></el-input>
                </el-form-item>
                <el-form-item label="昵称" prop="name" >
                    <el-input   auto-complete="off" v-model="form.name"></el-input>
                </el-form-item>
                <el-form-item label="选择角色" prop="chatRole" >
                     <el-input  min="0" auto-complete="off" v-model="form.chatRole" ></el-input>
                </el-form-item>
                <el-form-item label="登录密码" >
                    <el-input  placeholder="如不修改密码,请勿填写" auto-complete="off" v-model="form.password"></el-input>
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
    .transition-box {
        margin-bottom: 10px;
        width: 200px;
        height: 100px;
        border-radius: 4px;
        background-color: #409EFF;
        text-align: center;
        color: #fff;
        padding: 40px 20px;
        box-sizing: border-box;
        margin-right: 20px;
    }
    .route-animated{
        -webkit-animation-duration: .3s;
        animation-duration: .3s;
        -webkit-animation-fill-mode: both;
        animation-fill-mode: both;
    }
    .query-form {
        padding: 10px 25px 0;
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
                total: 0,
                pageSize: 20,
                listLoading: false,
                editDialogVisible:false,
                labelPosition:'right',
                searchForm:{
                    username:'',
                    chatRole:'',
                    chatStatus:'',
                    loginIp:'',
                },
                form:{
                    username:'',
                    name:'',
                    chatRole: '',
                    password:'',
                },
                rules:{
                    username:[
                        {required: true, message: '请输入用户名', trigger: 'blur'}
                    ],
                    name:[
                        {required: true, message: '请输入昵称', trigger: 'blur'}
                    ],
                    chatRole:[
                        {required: true, message: '请选择角色', trigger: 'blur'}
                    ]
                },
                tableData: []
            }

        },
        methods: {
            getData:function () {
                let _this = this,
                    query = {
                        rows: _this.pageSize,
                        page: _this.currentPage,
                        username: _this.searchForm.username,
                        chatRole: _this.searchForm.chatRole,
                        chatStatus: _this.searchForm.chatStatus,
                        loginIp: _this.searchForm.login_ip,
                    };
                _this.listLoading = true;
                window.axios.get('/user', { params: query }).then(function (response) {
                    let res = response.data;
                    if (res.status === 0) {
                        let data = res.data;
                        _this.tableData = data.data;
                        _this.total = data.total;
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
            handleEdit(index, row) {
                let _this = this;
                _this.editDialogVisible = true;
                _this.form.name = row.name;
                _this.form.nickname = row.nickname;
                _this.form.role = row.role;
            },
            handleDisable(index, row) {
                let _this = this;
                _this.$confirm('确定要对'+row.nickname+'禁言？', '提示', {type: 'warning'}).then( () => {

                });
            },
            handleOut(index, row) {
                let _this = this;
                _this.$confirm('确定要对'+row.nickname+'提出房间？', '提示', {type: 'warning'}).then( () => {

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
        },
        mounted() {
            this.getData();
        }
    }
</script>