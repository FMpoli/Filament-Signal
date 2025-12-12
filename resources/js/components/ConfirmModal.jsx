import React from 'react';

const ConfirmModal = ({
    isOpen,
    title,
    message,
    onConfirm,
    onCancel,
    confirmLabel = 'Delete',
    confirmColor = 'red',
    icon = 'trash'
}) => {
    if (!isOpen) return null;

    const colorClasses = {
        red: {
            header: 'bg-red-500',
            button: 'bg-red-500 hover:bg-red-600',
        },
        purple: {
            header: 'bg-purple-500',
            button: 'bg-purple-500 hover:bg-purple-600',
        },
        orange: {
            header: 'bg-orange-500',
            button: 'bg-orange-500 hover:bg-orange-600',
        },
        blue: {
            header: 'bg-blue-500',
            button: 'bg-blue-500 hover:bg-blue-600',
        },
    };

    const icons = {
        trash: (
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-6 h-6">
                <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
        ),
        warning: (
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-6 h-6">
                <path fillRule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
            </svg>
        ),
    };

    const colors = colorClasses[confirmColor] || colorClasses.red;

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50" onClick={onCancel}>
            <div
                className="bg-white dark:bg-slate-800 rounded-xl shadow-2xl overflow-hidden max-w-sm mx-4 transform transition-all"
                onClick={e => e.stopPropagation()}
            >
                {/* Colored Header with Icon */}
                <div className={`${colors.header} px-6 py-4 flex items-center gap-3`}>
                    <div className="text-white/90">
                        {icons[icon] || icons.trash}
                    </div>
                    <h3 className="text-lg font-bold text-white">{title}</h3>
                </div>

                {/* Body */}
                <div className="p-6">
                    <p className="text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                        {message}
                    </p>
                </div>

                {/* Footer with Buttons */}
                <div className="px-6 pb-6 flex justify-end gap-3">
                    <button
                        onClick={onCancel}
                        className="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 
                            bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 
                            rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={onConfirm}
                        className={`px-4 py-2 text-sm font-medium text-white ${colors.button} rounded-lg transition-colors shadow-sm`}
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ConfirmModal;
