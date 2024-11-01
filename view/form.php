<?php foreach($fields as $f): ?>
<p>
  <label for="<?php echo $this->get_field_id($f[0]); ?>">
     <?php echo  __($f[1]) ?>
  </label>
  <input class='widefat' type='text'
         id="<?php echo $this->get_field_id($f[0]); ?>"
         name="<?php echo $this->get_field_name($f[0]); ?>"
         value="<?php echo $f[2] ?>"/>
</p>
<?php endforeach; ?>

