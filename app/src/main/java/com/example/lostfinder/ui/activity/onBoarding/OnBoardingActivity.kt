package com.example.lostfinder.ui.activity.onBoarding

import android.content.Intent
import android.os.Bundle
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.lostfinder.databinding.ActivityOnBoardingBinding
import com.example.lostfinder.ui.activity.auth.AuthActivity
import com.example.lostfinder.utils.PrefManager

class OnBoardingActivity : AppCompatActivity() {
    private lateinit var binding: ActivityOnBoardingBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        binding = ActivityOnBoardingBinding.inflate(layoutInflater)
        setContentView(binding.root)
        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, 0, systemBars.right, 0)
            insets
        }

        val pref = PrefManager(this)
        val isOnBoarding = pref.getOnBoarding("onBoarding")

        if (isOnBoarding) {
            startActivity(Intent(this, AuthActivity::class.java))
            finish()
        }


        binding.startNowBtn.setOnClickListener {
            pref.saveOnBoarding("onBoarding", true)
            startActivity(Intent(this, AuthActivity::class.java))
            finish()
        }


    }
}