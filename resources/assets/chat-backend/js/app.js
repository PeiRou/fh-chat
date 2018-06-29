
require('./bootstrap');

window.Vue = require('vue');

import ElementUI from 'element-ui';
import VueRouter from 'vue-router';
import 'element-ui/lib/theme-chalk/index.css';
Vue.use(ElementUI);
Vue.use(VueRouter);

import util from './lib/util';
Vue.prototype.util = util;
Vue.component('s-identify', require('./components/login/identify'));
import App from './App.vue';
import Login from './components/login/login.vue';
import Index from './components/index/index.vue';
import User from './components/index/user.vue';
import Role from './components/index/role.vue';
import Room from './components/index/room.vue';
import Bullet from './components/index/bullet.vue';
import Admin from './components/index/admin.vue';
import Disable from './components/index/disable.vue';
import Packet from './components/index/packet.vue';
import PacketRecord from './components/index/packetRecord.vue';
import Platcfg from './components/index/platcfg.vue';


const routes = [
    {
        path: '/login',
        component: Login,
        hidden: true
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/user', component: User, name: '用户管理'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/role', component: Role, name: '角色管理'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/room', component: Room, name: '房间管理'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/bullet', component: Bullet, name: '公告管理'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/admin', component: Admin, name: '管理员管理'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/disable', component: Disable, name: '违禁词管理'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/packet', component: Packet, name: '红包管理'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/packetRecord', component: PacketRecord, name: '红包明细'},
        ]
    },
    {
        path: '/',
        component: Index,
        name:'',
        children: [
            {path: '/platcfg', component: Platcfg, name: '平台配置'},
        ]
    }
];

const router = new VueRouter({
    history: true,
    routes
});

const app = new Vue({
    el: '#app',
    router,
    render: h => h(App)
}).$mount('#app');
