<template>
    <div id="mainContent" style="height: 700px; overflow-y: auto; overflow-x: visible;">
        <div class="route-animated" >
            <div class="query-form">
                <el-form ref="searchForm" :inline="true"  :model="searchForm" size="mini" class="demo-form-inline">
                    <el-form-item  label="时间" >
                        <el-date-picker
                                v-model="searchForm.date"
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
                        <el-input  autocomplete="off" placeholder="红包ID" v-model="searchForm.packet_id"></el-input>
                    </el-form-item>
                    <el-form-item >
                        <el-input autocomplete="off" placeholder="订单号" v-model="searchForm.order"></el-input>
                    </el-form-item>
                    <el-form-item >
                        <el-input autocomplete="off" placeholder="用户名" v-model="searchForm.username"></el-input>
                    </el-form-item>
                    <el-form-item >
                        <el-select  clearable placeholder="全部" v-model="searchForm.status">
                            <el-option label="补发中" value="补发中" ></el-option>
                            <el-option label="成功" value="成功" ></el-option>
                            <el-option label="失败" value="失败" ></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item >
                        <el-input autocomplete="off" v-model="searchForm.min_money" placeholder="最小金额" ></el-input>
                    </el-form-item>
                    <el-form-item >
                        <el-input autocomplete="off" v-model="searchForm.max_money"  placeholder="最大金额" ></el-input>
                    </el-form-item>

                    <el-form-item>
                        <el-button type="info" v-on:click="getData" >查询</el-button>
                    </el-form-item>

                    <div style="float: right; margin-right:20px;">
                        <el-form-item>
                            <el-button type="success" v-on:click="handleEditAll" >一键补发</el-button>
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
                        prop="username"
                        label="用户名"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="packet_id"
                        label="红包ID"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="order"
                        label="订单号"
                        >
                </el-table-column>
                <el-table-column
                        prop="money"
                        label="金额"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="created_at"
                        label="领取时间"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="status"
                        label="状态"
                        width="180">
                    <template slot-scope="scope">
                        <span style="color: green" >{{scope.row.status}}</span>
                    </template>

                </el-table-column>
                <el-table-column label="操作">
                    <template slot-scope="scope">
                        <el-button size="mini" v-if="scope.row.status==='成功' || scope.row.status==='补发中'" disabled @click="handleEdit(scope.$index, scope.row)">补发</el-button>
                        <el-button size="mini" v-else @click="handleEdit(scope.$index, scope.row)">补发</el-button>
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
                searchForm:{
                    date: [new Date(), new Date()],  //format('yyyy-MM-dd')
                    packet_id:'',
                    order:'',
                    status:'',
                    username:'',
                    min_money:'',
                    max_money:'',
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
                tableData: [],
            }

        },
        methods: {
            getData:function () {
                let _this = this;
                let _date = [];
                if(_this.searchForm.date!=null){
                    _date = [new Date(_this.searchForm.date[0]).format('yyyy-MM-dd'),new Date(_this.searchForm.date[1]).format('yyyy-MM-dd')];
                }
                let query = {
                    rows: _this.pageSize,
                    page: _this.currentPage,
                    date: _date,
                    packet_id: _this.searchForm.packet_id,
                    order: _this.searchForm.order,
                    status: _this.searchForm.status,
                    username: _this.searchForm.username,
                    min_money: _this.searchForm.min_money,
                    max_money: _this.searchForm.max_money,
                };
                _this.listLoading = true;
                window.axios.get('/record', { params: query }).then(function (response) {
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
                    console.log(error);
                });
            },
            handleEdit(index, row) {
                let _this     = this,
                    _duration = 1000
                ;
                _this.$confirm('确定要补发该红包吗？', '提示', {type: 'warning'}).then( () => {
                    window.axios.put('/record/'+row.id).then(function (response) {
                        let res = response.data;
                        _this.listLoading = false;
                        _this.$message({
                            message: res.message,
                            type: res.status === 0 ? 'success' : 'error',
                            duration: _duration
                        });
                        _this.getData();
                    }).catch(function (error) {
                        _this.loading = false;
                        console.log(error);
                    });
                });
            },
            handleEditAll:function(){
                let _this     = this,
                    _duration = 1000
                ;
                _this.$confirm('确定要一键补发红包吗？', '提示', {type: 'warning'}).then( () => {
                    window.axios.post('/record/editAll').then(function (response) {
                        let res = response.data;
                        _this.listLoading = false;
                        _this.$message({
                            message: res.message,
                            type: res.status === 0 ? 'success' : 'error',
                            duration: _duration
                        });
                        _this.getData();
                    }).catch(function (error) {
                        _this.listLoading = false;
                        console.log(error);
                    });
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
            if(this.$route.query.packetId){
                this.searchForm.packet_id = this.$route.query.packetId ;
                this.searchForm.date = null;
            }
            this.getData();
        }
    }
</script>