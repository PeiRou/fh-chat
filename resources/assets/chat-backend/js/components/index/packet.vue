<template>
    <div id="mainContent" style="height: 700px; overflow-y: auto; overflow-x: visible;">
        <div class="route-animated" >
            <div class="query-form">
                <el-form ref="searchForm" :inline="true"   size="mini" class="demo-form-inline">
                    <el-form-item  label="时间" >
                            <el-date-picker
                                    v-model="serachForm.date"
                                    type="daterange"
                                    align="right"
                                    unlink-panels
                                    range-separator="至"
                                    start-placeholder="开始日期"
                                    end-placeholder="结束日期"
                                    :picker-options="pickerOptions">
                            </el-date-picker>
                    </el-form-item>
                    <el-form-item >
                        <el-input  placeholder="红包ID" autocomplete="off" v-model="serachForm.packet_id"></el-input>
                    </el-form-item>
                    <el-form-item label="房间">
                        <el-select clearable placeholder="全部" v-model="serachForm.type" >
                            <el-option label="爱彩聊天室" value="爱彩聊天室" ></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="状态">
                        <el-select  clearable placeholder="全部" v-model="serachForm.status">
                            <el-option label="疯抢中" value="疯抢中" ></el-option>
                            <el-option label="已抢完" value="已抢完" ></el-option>
                            <el-option label="已关闭" value="已关闭" ></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="info" v-on:click="getData" >查询</el-button>
                    </el-form-item>

                    <div style="float: right; margin-right:20px;">
                        <el-form-item>
                            <el-button type="success" @click="showDialog" >发红包</el-button>
                        </el-form-item>
                    </div>

                </el-form>
            </div>

            <el-table
                    :data="tableData"
                    border
                    v-loading="listLoading"
                    style="width: 100%">
                <el-table-column
                        prop="id"
                        label="红包ID"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="type"
                        label="房间"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="status"
                        label="状态"
                        width="100">
                    <template slot-scope="scope">
                        <span v-if="scope.row.status==='疯抢中'" style="color: green" >{{scope.row.status}}</span>
                        <span v-else-if="scope.row.status==='已关闭'" style="color: #9d9d9d" >{{scope.row.status}}</span>
                        <span v-else="scope.row.status==='已抢完'" style="color: red" >{{scope.row.status}}</span>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="money"
                        label="总金额"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="count"
                        label="红包个数"
                        width="100">
                    <template slot-scope="scope">
                        <span >{{scope.row.sel_count}}/{{scope.row.count}}</span>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="sel_money"
                        label="剩余金额"
                        width="100">
                </el-table-column>
                <el-table-column
                        prop="condition"
                        label="抢红包条件（最近两天）"
                        width="250">
                    <template slot-scope="scope">
                        <span >充值不少于{{ scope.row.recharge }} ,打码不少于{{scope.row.chip }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="created_at"
                        label="发送时间"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="created_hand"
                        label="操作人"
                        width="180">
                </el-table-column>

                <el-table-column label="操作">
                    <template slot-scope="scope">
                        <el-button size="mini" disabled v-if="scope.row.count===scope.row.sel_count || scope.row.status==='已关闭' || scope.row.status==='已抢完'"  @click="handleEdit(scope.$index, scope.row)">重发</el-button>
                        <el-button size="mini"  v-else="scope.row.count>=scope.row.sel_count"  @click="handleEdit(scope.$index, scope.row)">重发</el-button>
                        <el-button size="mini" disabled v-if="scope.row.count===scope.row.sel_count || scope.row.status==='已关闭' || scope.row.status==='已抢完'" @click="handleDisable(scope.$index, scope.row)">关闭</el-button>
                        <el-button size="mini"  v-else="scope.row.count>=scope.row.sel_count" @click="handleDisable(scope.$index, scope.row)">关闭</el-button>
                        <el-button size="mini" @click="handleOut(scope.$index, scope.row)">查看明细</el-button>
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
        <el-dialog :title="title" :visible.sync="editDialogVisible"  :label-position="labelPosition"  width="25%" v-loading="formLoading" >
            <el-form :model="form"  :rules="rules" ref="form" :label-position="labelPosition" label-width="120px" >
                <el-form-item label="选择房间" prop="type" >
                    <el-select v-model="form.type" placeholder="请选择">
                        <el-option label="爱彩聊天室" value="爱彩聊天室"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="红包总金额" prop="money" >
                    <el-input type="number" min="1"  auto-complete="off" v-model="form.money"></el-input>
                </el-form-item>
                <el-form-item label="红包总个数" prop="count" >
                    <el-input type="number" min="1" auto-complete="off" v-model="form.count" ></el-input>
                </el-form-item>
                <el-form-item label="最低充值金额" prop="recharge" >
                    <el-input type="number" min="0" placeholder="如不限制条件,请填写0" auto-complete="off" v-model="form.recharge"></el-input>
                </el-form-item>
                <el-form-item label="最低下注金额" prop="chip" >
                    <el-input type="number" min="0" placeholder="如不限制条件,请填写0" auto-complete="off" v-model="form.chip"></el-input>
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
                formLoading: false,
                editDialogVisible:false,
                labelPosition:'right',
                title:'',

                serachForm:{
                    date: [new Date(), new Date()],  //format('yyyy-MM-dd')
                    packet_id: '',
                    type: '',
                    status: '',
                },

                pickerOptions: {
                    shortcuts: [
                        {
                            text: '今天',
                            onClick(picker) {
                                const end = new Date();
                                const start = new Date();
                                //start.setTime(start.getTime() - 3600 * 1000 * 24);
                                picker.$emit('pick', [start, end]);
                            }
                        },
                        {
                            text: '昨天',
                            onClick(picker) {
                                const end = new Date();
                                const start = new Date();
                                start.setTime(start.getTime() - 3600 * 1000 * 24);
                                picker.$emit('pick', [start, end]);
                            }
                        },{
                        text: '最近一周',
                        onClick(picker) {
                            const end = new Date();
                            const start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                            picker.$emit('pick', [start, end]);
                        }
                    }, {
                        text: '最近一个月',
                        onClick(picker) {
                            const end = new Date();
                            const start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                            picker.$emit('pick', [start, end]);
                        }
                    }, {
                        text: '最近三个月',
                        onClick(picker) {
                            const end = new Date();
                            const start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                            picker.$emit('pick', [start, end]);
                        }
                    }]
                },
                form:{
                    id:'',
                    type:'',
                    money:'',
                    count: '',
                    chip:'',
                    recharge:'',
                },
                rules:{
                    type:[
                        {required: true, message: '请选择房间', trigger: 'blur'}
                    ],
                    money:[
                        {required: true, message: '请输入红包总金额', trigger: 'blur'}
                    ],
                    count:[
                        {required: true, message: '请输入红包总个数', trigger: 'blur'}
                    ],
                    recharge:[
                        {required: true, message: '请输入最低充值金额', trigger: 'blur'}
                    ],
                    chip:[
                        {required: true, message: '请输入最低下注金额', trigger: 'blur'}
                    ]
                },
                tableData: []
            }

        },
        methods: {
            getData:function () {
                let _this = this;
                let _date = [];
                if(_this.serachForm.date!=null){
                    _date = [new Date(_this.serachForm.date[0]).format('yyyy-MM-dd'),new Date(_this.serachForm.date[1]).format('yyyy-MM-dd')];
                }
                let query = {
                        rows: _this.pageSize,
                        page: _this.currentPage,
                        date: _date,
                        packet_id: _this.serachForm.packet_id,
                        type: _this.serachForm.type,
                        status: _this.serachForm.status,
                    };
                _this.listLoading = true;
                window.axios.get('/packet', { params: query }).then(function (response) {
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
            submit:function(form){
                let _this     = this ,
                    _duration = 1500;
                _this.$refs[form].validate((valid) => {
                    if(valid) {
                        _this.formLoading = true;
                        if(_this.form.id==''){
                            window.axios.post('/packet', _this.form).then(function (response) {
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
                            window.axios.put('/packet/'+_this.form.id, _this.form).then(function (response) {
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
            showDialog:function(){
                let _this = this;
                _this.initForm();
                _this.title = '发红包';
                _this.editDialogVisible = true;
            },
            handleEdit(index, row) {
                let _this = this;
                _this.editDialogVisible = true;
                _this.form.name = row.name;
                _this.form.nickname = row.nickname;
                _this.form.role = row.role;
            },
            handleDisable(index, row) {
                let _this     = this,
                    _duration = 1000,
                    query = {
                        status : '已关闭'
                    }
                ;
                _this.$confirm('确定要关闭该红包选项吗？', '提示', {type: 'warning'}).then( () => {
                    window.axios.post('/packet/'+row.id,query).then(function (response) {
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
                });
            },
            handleOut(index, row) {
                let _this = this;
                _this.$router.push({
                    path: 'packetRecord',
                    query: {
                        packetId: row.id,
                    }
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
                this.form.type     = '';
                this.form.money    = '';
                this.form.count    = '';
                this.form.chip     = '';
                this.form.recharge = '';
            }
        },
        mounted() {
            this.getData();
        }
    }
</script>