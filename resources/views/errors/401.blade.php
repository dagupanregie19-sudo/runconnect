@extends('errors.layout')

@section('title', 'Unauthorized')
@section('code', '401')
@section('icon', 'fa-lock')
@section('message', 'Unauthorized')
@section('description', 'You need to log in to access this page. Please sign in with your RunConnect account.')