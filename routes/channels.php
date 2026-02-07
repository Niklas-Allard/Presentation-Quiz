<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for all presentation participants
Broadcast::channel('presentation.{presentationId}', function () {
    return true; // Public channel - no authorization needed
});

// Private channel for presentation admins
Broadcast::channel('presentation.{presentationId}.admin', function ($user, $presentationId) {
    // Only authenticated users can access admin channel
    return $user !== null;
});
