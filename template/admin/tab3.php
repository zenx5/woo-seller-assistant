<?php
    function get_user_name($id) {
        $user = get_user_by('ID', $id);
        if( $user ) {
            return $user->data->display_name;
        }
        return $id;
    }
?>
<div style="padding:20px 0px">
    <table>
        <tr>
            <th>ID</th>
            <th>Mensaje</th>
            <th>Usuario</th>
            <th>Ubicacion</th>
        </tr>
        <?php foreach($logs as $log):?>
            <tr style="border:1px solid black;">
                <td style="border:1px solid black; padding:5px 40px;"><?=$log->id?></td>
                <td style="border:1px solid black; padding:5px 40px;"><?=$log->message?></td>
                <td style="border:1px solid black; padding:5px 40px;"><?=get_user_name($log->user_id)?></td>
                <td style="border:1px solid black; padding:5px 40px;">"<?=$log->file?>" en Linea <?=$log->line?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
