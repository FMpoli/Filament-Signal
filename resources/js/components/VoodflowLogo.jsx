import React from 'react';

/**
 * VoodflowLogo Component
 * 
 * Animated logo showing data flowing through nodes
 * Purple (Vood) + Emerald (Flow) gradient lines with animated packets
 */
const VoodflowLogo = ({ width = 100, height = 100, className = '' }) => {
    return (
        <svg
            width={width}
            height={height}
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
        >
            <style>
                {`
                /* Flow animation */
                @keyframes voodFlowAnimation {
                    from {
                        stroke-dashoffset: 24;
                    }
                    to {
                        stroke-dashoffset: 0;
                    }
                }
                
                .flow-anim {
                    animation: voodFlowAnimation 1s linear infinite;
                }
                `}
            </style>

            <defs>
                {/* Purple Gradient (Vood) */}
                <linearGradient id="voodGrad" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                    <stop stopColor="#a78bfa" />
                    <stop offset="1" stopColor="#7c3aed" />
                </linearGradient>

                {/* Emerald Gradient (Flow) */}
                <linearGradient id="flowGrad" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                    <stop stopColor="#34d399" />
                    <stop offset="1" stopColor="#059669" />
                </linearGradient>
            </defs>

            {/* 1. Background lines (Static and semi-transparent) */}
            <path d="M25 25 Q 25 75 50 85" stroke="#4c1d95" strokeWidth="10" strokeLinecap="round" opacity="0.5" />
            <path d="M75 25 Q 75 75 50 85" stroke="#064e3b" strokeWidth="10" strokeLinecap="round" opacity="0.5" />

            {/* 2. Colored lines (Main body) */}
            <path d="M25 25 Q 25 75 50 85" stroke="url(#voodGrad)" strokeWidth="6" strokeLinecap="round" />
            <path d="M75 25 Q 75 75 50 85" stroke="url(#flowGrad)" strokeWidth="6" strokeLinecap="round" />

            {/* 3. Animated data packets (Flow effect) */}
            <path
                d="M25 25 Q 25 75 50 85"
                stroke="white"
                strokeWidth="2"
                strokeLinecap="round"
                fill="none"
                strokeDasharray="4 20"
                className="flow-anim"
                opacity="0.6"
            />
            <path
                d="M75 25 Q 75 75 50 85"
                stroke="white"
                strokeWidth="2"
                strokeLinecap="round"
                fill="none"
                strokeDasharray="4 20"
                className="flow-anim"
                opacity="0.6"
            />

            {/* 4. Nodes (Circles at endpoints) */}
            <circle cx="25" cy="25" r="7" fill="#8b5cf6" stroke="#2e1065" strokeWidth="2" />
            <circle cx="75" cy="25" r="7" fill="#10b981" stroke="#2e1065" strokeWidth="2" />

            {/* Central node (Output) */}
            <circle cx="50" cy="85" r="7" fill="white" stroke="#2e1065" strokeWidth="2" />
        </svg>
    );
};

export default VoodflowLogo;
