package com.example.lostfinder.ui.activity

import android.os.Bundle
import android.transition.TransitionManager
import android.view.View
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.constraintlayout.widget.ConstraintSet
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.fragment.app.Fragment
import com.example.lostfinder.R
import com.example.lostfinder.databinding.ActivityMainBinding
import com.example.lostfinder.databinding.CustomBottomNavBinding
import com.example.lostfinder.ui.fragment.HomeFragment
import com.example.lostfinder.ui.fragment.ProfileFragment
import com.example.lostfinder.ui.fragment.ScanFragment

class MainActivity : AppCompatActivity() {
    private lateinit var binding: ActivityMainBinding

    private lateinit var bottomNavBinding: CustomBottomNavBinding

    private var isCurrentTab: String = "home"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        val bottomNav = findViewById<View>(R.id.bottomNav)
        bottomNavBinding = CustomBottomNavBinding.bind(bottomNav)


        bottomNavBinding.homeNav.setOnClickListener {
            if (isCurrentTab != "home") {
                  loadFragment(HomeFragment())
                isCurrentTab = "home"
                navText(bottomNavBinding.homeNavText)
                moveIndicator(R.id.home_nav, R.color.indi_color_green)
            }
        }

        bottomNavBinding.scanNav.setOnClickListener {
            if (isCurrentTab != "scan") {
                  loadFragment(ScanFragment())
                isCurrentTab = "scan"
                navText(bottomNavBinding.scanNavText)
                moveIndicator(R.id.scan_nav, R.color.indi_color_orange)
            }
        }

        bottomNavBinding.profileNav.setOnClickListener {
            if (isCurrentTab != "profile") {
                  loadFragment(ProfileFragment())
                isCurrentTab = "profile"
                navText(bottomNavBinding.profileNavText)
                moveIndicator(R.id.profile_nav, R.color.indi_color_teal)
            }
        }

        if (savedInstanceState == null) {
            loadFragment(HomeFragment())
            isCurrentTab = "home"
            navText(bottomNavBinding.homeNavText)
            moveIndicator(R.id.home_nav, R.color.indi_color_green)
        }


    }

    private fun moveIndicator(targetViewId: Int, colorResId: Int) {
        val constraintSet = ConstraintSet()
        val parent = bottomNavBinding.root
        constraintSet.clone(parent)

        // Change indicator color
//        bottomNavBinding.indicatorView.background = ContextCompat.getDrawable(this, R.drawable.indicator)

        // Set background color dynamically
        val color = ContextCompat.getColor(this, colorResId)
        val drawable = ContextCompat.getDrawable(this, R.drawable.indicator)?.mutate()
        drawable?.setTint(color)
        bottomNavBinding.indicatorView.background = drawable

        // Move indicator to center of the clicked nav
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.START,
            targetViewId,
            ConstraintSet.START
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.END,
            targetViewId,
            ConstraintSet.END
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.TOP,
            targetViewId,
            ConstraintSet.TOP
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.BOTTOM,
            targetViewId,
            ConstraintSet.BOTTOM
        )

        TransitionManager.beginDelayedTransition(parent)
        constraintSet.applyTo(parent)

        // Bring to front
//        bottomNavBinding.indicatorView.bringToFront()
    }


    private fun navText(view: View) {
     /*   // Hide all
        bottomNavBinding.homeNavText.visibility = View.GONE
        bottomNavBinding.scanNavText.visibility = View.GONE
        bottomNavBinding.profileNavText.visibility = View.GONE

        // Show only selected
        view.visibility = View.VISIBLE*/

        val allTextViews = listOf(
            bottomNavBinding.homeNavText,
            bottomNavBinding.scanNavText,
            bottomNavBinding.profileNavText
        )

        for (textView in allTextViews) {
            if (textView == view) {
                textView.apply {
                    alpha = 0f
                    visibility = View.VISIBLE
                    animate().alpha(1f).setDuration(500).start()
                }
            } else {
                textView.visibility = View.GONE
            }
        }
    }


    private fun loadFragment(fragment: Fragment) {
        supportFragmentManager.beginTransaction()
            .replace(R.id.container_layout, fragment)
            .commit()
    }
}