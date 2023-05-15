<footer>
    <p class="t_center txt_fotter">Copyright © MON MARCHE All Right Reserved.
    </p>
</footer>
<?php if(is_single()): ?>
    <?php $getDate = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS); ?>
    <?php $weekSet = ['[日]', '[月]', '[火]', '[水]', '[木]', '[金]', '[土]']; ?>
        <footer class="new03footer">        
            <ul class="display_flex_stretch new03footerTab new03footerTab_<?php print_r(count(scf::get('flyerImg'))); ?>">
                <?php $i=1; foreach( scf::get('newLoops',get_the_ID()) as $field ): $cahckdate = 'slide_'.$i; ?>
                <li class="linew03footerTab linew03footerTab_<?php print_r(count(scf::get('flyerImg'))); ?> <?php if($cahckdate==$getDate){ echo 'active'; }else{ if(empty($getDate) and $i===1){echo 'active';} else{echo 'nonactive';} } ?>" data-openClass=".actionNew03_<?php echo $i; ?>">
                    <?php $week = date('w', strtotime( $field['date'])); ?>
                    <?php echo date('n月j日', strtotime( $field['date'])).$weekSet[$week]; ?>
                </li>
                <?php $i++; endforeach; ?>
            </ul>
        </footer>
<?php endif; ?>