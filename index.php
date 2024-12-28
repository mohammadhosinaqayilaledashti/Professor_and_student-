<?php
session_start();
date_default_timezone_set('Asia/Tehran');
require '../app/lib/jdf.php';
require '../config/database.php';
require '../app/functions.php';
require '../app/ajax/ajax_handler.php';

$uri = $_SERVER['REQUEST_URI'];
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    handleAjaxRequest();
} else {
    $uri = _getPathBeforeQuery($uri);    // Handle normal requests
    if ($uri == '/dashboard/admin/profile') {
        require '../app/views/admin/profile.php';
    } else if ($uri == '/dashboard/student/profile') {
        require '../app/views/student/profile.php';
    } else if ($uri == '/dashboard/teacher/profile') {
        require '../app/views/teacher/profile.php';
    } else if ($uri == '/') {
        require '../app/views/home.php';
    } else if ($uri == '/dashboard/admin/list_tickets' || $uri == '/dashboard/admin/list_tickets/') {
        require '../app/views/admin/list_tickets.php';
    } else if ($uri == '/dashboard/admin/answer_ticket' || $uri == '/dashboard/admin/answer_ticket/') {
        require '../app/views/admin/answer_ticket.php';
    } else if ($uri == '/dashboard/admin') {
        require '../app/views/admin/panel_admin.php';
    } else if ($uri == '/dashboard/admin/await_user_list') {
        require '../app/views/admin/await_user_list.php';
    } else if ($uri == '/dashboard/admin/await_request_register_lesson_list') {
        require '../app/views/admin/await_request_register_lesson_list.php';
    } elseif ($uri == '/dashboard/teacher' || $uri == '/dashboard/teacher/') {
        require '../app/views/teacher/panel_teacher.php';
    } elseif ($uri == '/dashboard/student' || $uri == '/dashboard/student/') {
        require '../app/views/student/panel_student.php';
    } elseif ($uri == '/dashboard/teacher/tickets' || $uri == '/dashboard/teacher/tickets/') {
        require '../app/views/teacher/ticket.php';
    } elseif ($uri == '/dashboard/student/tickets' || $uri == '/dashboard/student/tickets/') {
        require '../app/views/student/ticket.php';
    } elseif ($uri == '/dashboard/teacher/list_my_lessons' || $uri == '/dashboard/teacher/list_my_lessons/') {
        require '../app/views/teacher/list_my_lessons.php';
    } elseif ($uri == '/dashboard/student/list_my_lessons' || $uri == '/dashboard/student/list_my_lessons/') {
        require '../app/views/student/list_my_lessons.php';
    } elseif ($uri == '/dashboard/teacher/request_register_lesson') {
        require '../app/views/teacher/request_register_lesson.php';
    } elseif ($uri == '/dashboard/student/list_notifications' || $uri == '/dashboard/student/list_notifications/') {
        require '../app/views/student/list_notifications.php';
    } elseif ($uri == '/dashboard/student/show_notification' || $uri == '/dashboard/student/show_notification/') {
        require '../app/views/student/show_notification.php';
    } elseif ($uri == '/dashboard/teacher/list_notifications' || $uri == '/dashboard/teacher/list_notifications/') {
        require '../app/views/teacher/list_notifications.php';
    } elseif ($uri == '/dashboard/teacher/show_notification' || $uri == '/dashboard/teacher/show_notification/') {
        require '../app/views/teacher/show_notification.php';
    } elseif ($uri == '/dashboard/teacher/student_requests_for_lesson' || $uri == '/dashboard/teacher/student_requests_for_lesson/') {
        require '../app/views/teacher/student_requests_for_lesson.php';
    } elseif ($uri == '/dashboard/teacher/panel_lesson' || $uri == '/dashboard/teacher/panel_lesson/') {
        require '../app/views/teacher/panel_lesson.php';
    } elseif ($uri == '/dashboard/student/panel_lesson' || $uri == '/dashboard/student/panel_lesson/') {
        require '../app/views/student/panel_lesson.php';
    } elseif ($uri == '/dashboard/teacher/upload_session' || $uri == '/dashboard/teacher/upload_session/') {
        require '../app/views/teacher/upload_session.php';
    } elseif ($uri == '/dashboard/student/show_public_message' || $uri == '/dashboard/student/show_public_message/') {
        require '../app/views/student/show_public_message.php';
    } elseif ($uri == '/dashboard/teacher/send_comment' || $uri == '/dashboard/teacher/send_comment/') {
        require '../app/views/teacher/send_comment.php';
    } elseif ($uri == '/dashboard/student/send_comment' || $uri == '/dashboard/student/send_comment/') {
        require '../app/views/student/send_comment.php';
    } elseif ($uri == '/dashboard/student/send_comment' || $uri == '/dashboard/student/send_comment/') {
        require '../app/views/student/send_comment.php';
    } elseif ($uri == '/dashboard/student/list_assignments' || $uri == '/dashboard/student/list_assignments/') {
        require '../app/views/student/list_assignments.php';
    } elseif ($uri == '/dashboard/student/upload_assignments' || $uri == '/dashboard/student/upload_assignments/') {
        require '../app/views/student/upload_assignments.php';
    } elseif ($uri == '/dashboard/teacher/show_assignments_student' || $uri == '/dashboard/teacher/show_assignments_student/') {
        require '../app/views/teacher/show_assignments_student.php';
    } elseif ($uri == '/db') {
        require "../create_tables.php";
        exit;
    } elseif ($uri == '/register') {
        require "../create_tables.php";
        exit;
    } elseif ($uri == '/login') {
        require '../app/views/login.php';
        exit;
    } elseif ($uri == '/dashboard') {
        require '../app/views/dashboard.php';
        exit;
    } elseif ($uri == '/login') {
        require '../app/views/login.php';
        exit;
    } elseif ($uri == '/dashboard/student/register_in_lesson' or $uri == '/dashboard/student/register_in_lesson/') {
        require '../app/views/student/register_in_lesson.php';
        exit;
    } elseif ($uri == '/logout') {
        session_destroy();
        session_unset(); // Corrected: Removed the argument
        header('Location: /login');
        exit;
    } else {
        echo "Page not found";
    }
}
