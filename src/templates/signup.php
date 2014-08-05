<div class="signup">
    <form name="frmSignup" action="" method="" class="frmSignup">
        <div class="form-input">
            <label for="username">Имя</label>
            <input type="text" name="username" class="username" placeholder="Имя пользователя" required="required">
        </div>
        <div class="form-input">
            <label for="pass">Пароль</label>
            <input type="password" name="pass" class="pass" placeholder="Пароль" required="required">
        </div>
        <div class="form-input">
            <label for="role">Выберите роль</label>
            <select name="role" class="role" required="required">
                <option value="client">Заказчик</option>
                <option value="executor">Исполнитель</option>
            </select>
        </div>
        <div class="form-input">
            <button class="doSignup">Регистрация</button>
        </div>
    </form>
</div>