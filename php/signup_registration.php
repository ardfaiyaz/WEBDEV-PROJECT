<?php
session_start();
require_once 'database.php';

if (isset($_POST['register'])) {
    $user_id = $_POST['user_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $middlename = $_POST['middlename'];
    $gender = $_POST['gender'];
    $nationality = $_POST['nationality'];
    $birthdate = $_POST['birthdate'];
    $birthplace = $_POST['birthplace'];
    $tel_num = $_POST['tel_num'];
    $mobile_num = $_POST['mobile_num'];
    $house_no = $_POST['house_no'];
    $street = $_POST['street'];
    $brgy = $_POST['brgy'];
    $city_mun = $_POST['city_mun'];
    $province = $_POST['province']; 
    $zip $_POST['zip'];

    $user_id = $conn->query("SELECT user_id FROM student_info WHERE user_id = '$user_id'");
    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = 'User is already registered!';
        $_SESSION['active_form'] = 'Success';
    } else {
        $conn->query("INSERT INTO student_info (user_id, firstname, lastname, middlename, gender, nationality, birthdate, birthplace, tel_num, mobile_num, house_no, street, brgy, city_mun, province, zip) 
                    VALUES ('$user_id', '$firstname', '$lastname', '$middlename', '$gender', '$nationality', '$birthdate', '$birthplace', '$tel_num', '$mobile_num', '$house_no', '$street', '$brgy', '$city_mun', '$province', '$zip')");
    }

    header("Location: signup.html");
    exit();
}

?>