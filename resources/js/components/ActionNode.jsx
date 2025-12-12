import React, { useState } from 'react';
import { Handle, Position } from 'reactflow';
import { cn } from '../utils/classNames';
import ConfirmModal from './ConfirmModal';

const ActionNode = ({ id, data }) => {
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    // Determine color based on action type
    const isWebhook = data.actionType === 'webhook';

    // Tailwind classes based on type
    const headerGradient = isWebhook
        ? 'bg-gradient-to-r from-blue-600 to-blue-700'
        : 'bg-gradient-to-r from-gray-600 to-gray-700';
    const handleColor = isWebhook ? '!bg-blue-600' : '!bg-gray-600';

    const handleDelete = (e) => {
        e.stopPropagation();
        setShowDeleteModal(true);
    };

    const confirmDelete = () => {
        setShowDeleteModal(false);
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteAction', id);
        }
    };

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete Action"
                message={`Are you sure you want to delete "${data.label || 'this action'}"?`}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
                confirmColor="red"
            />

            <div className={cn(
                'box-border',
                'bg-white dark:bg-slate-900',
                'border border-solid rounded-xl',
                isWebhook ? 'border-blue-500 dark:border-blue-700' : 'border-gray-500 dark:border-gray-700',
                'text-slate-700 dark:text-slate-200',
                'min-w-[240px] max-w-[280px]',
                'shadow-md dark:shadow-lg',
                'overflow-hidden'
            )}>
                {/* Header */}
                <div className={cn(headerGradient, 'px-4 py-2 flex items-center justify-between box-border')}>
                    <div className="flex items-center gap-2">
                        {isWebhook ? (
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                            </svg>
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                                <path fillRule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.923 1.042A1 1 0 0113.88 18H6.12a1 1 0 01-.75-1.65L6.5 15H5a2 2 0 01-2-2V5zm11 1H6v8h8V6z" clipRule="evenodd" />
                            </svg>
                        )}
                        <div className="text-xs font-bold text-white uppercase tracking-wider">
                            {data.label || 'Action'}
                        </div>
                    </div>

                    <button
                        onClick={handleDelete}
                        className="nodrag cursor-pointer text-white/80 hover:text-white transition-colors"
                        title="Delete Action"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-3.5 h-3.5">
                            <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                        </svg>
                    </button>
                </div>

                {/* Body */}
                <div className="p-3 px-4">
                    {/* Description */}
                    {data.description && (
                        <div className="text-slate-500 dark:text-slate-400 text-xs italic mb-2">
                            {data.description}
                        </div>
                    )}

                    {/* Action Type Badge */}
                    <div className="text-xs text-slate-500 dark:text-slate-400 font-mono bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded inline-block">
                        {data.actionType || 'action'}
                    </div>
                </div>

                <Handle
                    type="target"
                    position={Position.Left}
                    className={cn(handleColor, '!w-2.5 !h-2.5 !-left-[5px]')}
                />
            </div>
        </>
    );
};

export default ActionNode;
