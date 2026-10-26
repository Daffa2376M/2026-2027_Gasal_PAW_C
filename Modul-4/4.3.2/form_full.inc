<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
    <p>
        <label for="surname">Surname:</label><br>
        <input type="text" id="surname" name="surname"
               value="<?php echo isset($_POST['surname']) ? htmlspecialchars($_POST['surname']) : ''; ?>">
    </p>
    <p>
        <label for="email">Email address:</label><br>
        <input type="text" id="email" name="email"
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
    </p>
    <p>
        <label for="password">Password (min 6 char):</label><br>
        <input type="password" id="password" name="password">
    </p>
    <p>
        <label for="address">Street Address:</label><br>
        <textarea id="address" name="address" rows="3" cols="35"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
    </p>
    <p>
        <label for="state">State:</label><br>
        <select id="state" name="state">
            <option value="">-- Pilih State --</option>
            <?php
            $states = ["New South Wales", "Victoria", "Queensland", "South Australia", "Western Australia", "Tasmania", "Northern Territory", "ACT"];
            $currentState = isset($_POST['state']) ? $_POST['state'] : '';
            foreach ($states as $s) {
                $selected = ($currentState === $s) ? 'selected' : '';
                echo "<option value=\"$s\" $selected>$s</option>";
            }
            ?>
        </select>
    </p>
    <input type="hidden" name="country" value="Australia">
    <p>
        Gender:<br>
        <?php $gender = isset($_POST['gender']) ? $_POST['gender'] : ''; ?>
        <label><input type="radio" name="gender" value="Male" <?php echo ($gender === 'Male') ? 'checked' : ''; ?>> Male</label>
        <label><input type="radio" name="gender" value="Female" <?php echo ($gender === 'Female') ? 'checked' : ''; ?>> Female</label>
    </p>
    <p>
        <label><input type="checkbox" name="vegetarian" value="Yes" <?php echo isset($_POST['vegetarian']) ? 'checked' : ''; ?>> Vegetarian?</label>
    </p>
    <p>
        <input type="submit" name="submit" value="Submit">
        <input type="reset" value="Reset">
    </p>
</form>
