import { select } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { Button, Modal, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const { classifaiChatGPTData } = window;

const RenderData = ({ data }) => {
	if (!data) {
		return null;
	}

	return (
		<>
			{data.map((item, i) => {
				return (
					<div className="classifai-title" key={i}>
						<textarea rows="3">{item}</textarea>
						<Button
							variant="secondary"
							onClick={() => {
								console.log('Click');
							}}
						>
							{__('Select', 'classifai')}
						</Button>
					</div>
				);
			})}
		</>
	);
};

const PostStatusInfo = () => {
	const [isLoading, setIsLoading] = useState(false);
	const [isOpen, setOpen] = useState(false);
	const [data, setData] = useState([]);

	if (!classifaiChatGPTData || !classifaiChatGPTData.enabledFeatures) {
		return null;
	}

	// Ensure the user has proper permissions
	if (
		classifaiChatGPTData.noPermissions &&
		1 === parseInt(classifaiChatGPTData.noPermissions)
	) {
		return null;
	}

	const openModal = () => setOpen(true);
	const closeModal = () => setOpen(false) && setData([]);
	const postId = select('core/editor').getCurrentPostId();

	return (
		<PluginPostStatusInfo>
			{classifaiChatGPTData.enabledFeatures.map((feature) => {
				const path = feature?.path + postId;
				return (
					<div key={feature?.feature}>
						{isOpen && (
							<Modal
								title={feature?.modalTitle}
								onRequestClose={closeModal}
								isFullScreen={false}
								className="title-modal"
							>
								{isLoading ? (
									<Spinner />
								) : (
									<RenderData data={data} />
								)}
							</Modal>
						)}
						<Button
							variant="secondary"
							onClick={async () => {
								setIsLoading(true);
								openModal();
								apiFetch({
									path,
								}).then((res) => {
									setData(res);
									setIsLoading(false);
								});
							}}
						>
							{feature?.buttonText}
						</Button>
					</div>
				);
			})}
		</PluginPostStatusInfo>
	);
};

registerPlugin('classifai-status-info', { render: PostStatusInfo });
