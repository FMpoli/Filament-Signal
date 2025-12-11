import React from 'react';
import { Handle, Position } from 'reactflow';

const FilterNode = ({ data }) => {
    const filters = Array.isArray(data.filters) ? data.filters : [];
    const matchType = data.matchType || 'all';
    const matchLabel = matchType === 'all' ? 'ALL CONDITIONS' : 'ANY CONDITION';

    // Helper map for operators
    const operatorMap = {
        'equals': 'Equals',
        'not_equals': 'Not equals',
        'contains': 'Contains',
        'not_contains': 'Not contains',
        'greater_than': '>',
        'greater_than_or_equal': '>=',
        'less_than': '<',
        'less_than_or_equal': '<=',
        'in': 'In',
        'not_in': 'Not in',
    };

    return (
        <div className="
            bg-white dark:bg-slate-800 
            border border-slate-200 dark:border-slate-700 
            rounded-xl 
            text-slate-700 dark:text-slate-200 
            min-w-[300px] max-w-[400px] 
            shadow-lg dark:shadow-[0_10px_15px_-3px_rgba(0,0,0,0.5),0_4px_6px_-2px_rgba(0,0,0,0.3)]
            overflow-hidden
        ">
            <Handle type="target" position={Position.Left} className="!bg-purple-500 !w-2.5 !h-2.5 !-left-[5px]" />

            {/* Header */}
            <div className="bg-purple-500 px-4 py-2 flex items-center justify-between">
                <div className="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                        <path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />
                    </svg>
                    <div className="text-xs font-bold text-white uppercase transform translate-y-px">Filter Logic</div>
                </div>
                <div
                    className="nodrag cursor-pointer text-white/80 hover:text-white flex ml-2 transition-colors"
                    onClick={(e) => {
                        e.stopPropagation();
                        if (confirm('Are you sure you want to delete all filters?')) {
                            if (data.livewireId && window.Livewire) {
                                window.Livewire.find(data.livewireId).call('deleteFilters');
                            }
                        }
                    }}
                    title="Delete Filters"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-3.5 h-3.5">
                        <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                </div>
            </div>

            {/* Body */}
            <div className="p-3 px-4">
                {/* Conditions List */}
                {filters.length > 0 ? (
                    <div className="flex flex-col gap-2">
                        {filters.map((filter, index) => (
                            <div key={index} className="
                                bg-slate-50 dark:bg-slate-900 
                                rounded-md p-2 px-3 
                                text-xs 
                                border border-slate-200 dark:border-slate-700 
                                flex items-center gap-2 flex-wrap
                            ">
                                <span className="text-slate-500 dark:text-slate-400 font-medium">{filter.data?.field || 'Field'}</span>
                                <span className="text-orange-500 font-bold">{operatorMap[filter.type] || filter.type}</span>
                                <span className="text-slate-700 dark:text-slate-200 font-medium">{filter.data?.value}</span>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-[13px] text-slate-400 dark:text-slate-500 italic">No filters configured</div>
                )}

                {/* Match Type Badge (Footer) */}
                <div className="mt-3 border-t border-slate-200 dark:border-slate-700 pt-2 flex justify-end">
                    <div className="text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-100 px-2 py-1 rounded uppercase">
                        {matchLabel}
                    </div>
                </div>
            </div>

            <Handle type="source" position={Position.Right} className="!bg-purple-500 !w-2.5 !h-2.5 !-right-[5px]" />
        </div>
    );
};

export default FilterNode;
