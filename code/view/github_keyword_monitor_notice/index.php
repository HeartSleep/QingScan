{include file='public/head' /}
<?php
$dengjiArr = ['Low', 'Medium', 'High', 'Critical'];
?>


    <?php
    $searchArr = [
        'action' => $_SERVER['REQUEST_URI'],
        'method' => 'get',
        'inputs' => [
            ['type' => 'text', 'name' => 'search', 'placeholder' =>'search'],
        ],
        /*'btnArr' => [
            ['text' => '添加', 'ext' => [
                "href" => url('add'),
                "class" => "btn btn-outline-success"
            ]]
        ]*/
    ];

    ?>
    {include file='public/search' /}




<div class="col-md-12 ">
    <div class="row tuchu">
        <div class="col-md-12 ">
            <table class="table table-bordered table-hover table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>关键字</th>
                    <th>名称</th>
                    <th>文件路径</th>
                    <th>url地址</th>
                </tr>
                </thead>
                <?php foreach ($list as $value) { ?>
                    <tr>
                        <td><?php echo $value['id'] ?></td>
                        <td><?php echo $value['title'] ?></td>
                        <td><?php echo $value['name'] ?></td>
                        <td><?php echo $value['path'] ?></td>
                        <td><?php echo $value['html_url'] ?></td>
                        <td><?php echo $value['create_time'] ?></td>

                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
    {include file='public/fenye' /}
</div>
{include file='public/footer' /}