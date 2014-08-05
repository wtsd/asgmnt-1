<form name="frmOrder" class="frmOrder" action="" method="">
    <h2>Добавление заказа</h2>
    <div class="form-input">
        <label for="caption">Заголовок</label>
        <input type="text" name="caption" class="caption" placeholder="Короткое описание заказа" required="required">
    </div>
    <div class="form-input">
        <label for="descr">Описание</label>
        <textarea name="descr" class="descr" placeholder="Подробное описание заказа" required="required"></textarea>
    </div>
    <div class="form-input">
        <label for="price">Цена</label>
        <input type="text" name="price" class="price" placeholder="100" required="required">
    </div>
    <div class="form-input">
        <button class="saveOrder">Сохранить</button>
    </div>
</form>