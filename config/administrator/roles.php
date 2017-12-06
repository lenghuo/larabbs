<?php
/**
 * Created by PhpStorm.
 * User: wxp
 * Date: 2017/12/6
 * Time: 下午3:12
 */
use Spatie\Permission\Models\Role;

return [
    'title' => '角色',
    'single' => '角色',
    'model' => Role::class,

    'permission' => function () {
        return Auth::user()->can('manage_users');
    },

    'columns' => [
        'id' => [
            'title' => 'ID',
        ],
        'name' => [
            'title' => '标识',
        ],
        'permissions' => [
            'titlle' => '权限',
            'output' => function ($value,$model) {
                $model->load('permissions');
                $result = [];
                foreach ($model->permissions as $permission) {
                    $result[] = $permission->name;
                }

                return empty($result) ? 'N/A' : implode($result,'|');
            },
            'sortable' => false,
        ],

        'operation' => [
            'title' => '管理',
            'output' => function($value,$model){
                return $value;
            },
            'sortable' => false,
        ],
    ],

    'edit_fields' => [
        'name' => [
            'name' => '标识',
        ],

        'permissions' => [
            'type' => 'relationship',
            'title' => '权限',
            'name_field' => 'name',
        ],
    ],

    'filters' => [
        'id' => [
            'title' => 'ID',
        ],
        'name' => [
            'title' => '标识',
        ],
    ],

    'rules' => [
        'name' => 'required|max:15|unique:roles.name',
    ],

    'messages' => [
        'name.required' => '标识不能为空',
        'name.unique' => '标识已存在',
    ],

];