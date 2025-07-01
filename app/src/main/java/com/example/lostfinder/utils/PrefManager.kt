package com.example.lostfinder.utils

import android.R
import android.content.Context
import android.content.SharedPreferences

class PrefManager(context : Context) {
    private val PREF_NAME = "lost_finder_pref"
    private val prefs : SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)

    fun saveOnBoarding(key: String, value: Boolean) {
        prefs.edit().putBoolean(key, value).apply()
    }

    fun getOnBoarding(key: String) : Boolean {
        return prefs.getBoolean(key, false)
    }

    fun clearAll() {
        prefs.edit().clear().apply()
    }
}