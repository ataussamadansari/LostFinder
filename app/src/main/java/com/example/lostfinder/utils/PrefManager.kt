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

    fun saveIsLogin(key1: String, value1: Boolean, key2: String, value2 : String) {
        prefs.edit().apply {
            putBoolean(key1, value1)   // Example: key1 = "isLogin", value1 = true
            putString(key2, value2)    // Example: key2 = "userId", value2 = "123"
            apply()                    // Save changes asynchronously
        }
    }

    fun getNumber(key: String) : String? {
        return prefs.getString(key, "N/A")
    }

    fun checkIsLogin(key1: String) : Boolean {
        return prefs.getBoolean(key1, false)
    }

    fun clearAll() {
        prefs.edit().clear().apply()
    }
}